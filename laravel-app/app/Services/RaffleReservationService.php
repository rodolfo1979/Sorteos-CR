<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Raffle;
use App\Models\RaffleNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RaffleReservationService
{
    public function randomNumbers(Raffle $raffle, int $packageCount): array
    {
        $quantity = $this->quantityFor($raffle, $packageCount);
        $this->releaseExpiredReservationsIfDue($raffle);

        $selected = collect();
        $candidatePoolSize = min($raffle->total_numbers, max($quantity * 12, 80));

        for ($attempt = 0; $attempt < 5 && $selected->count() < $quantity; $attempt++) {
            $candidates = $this->randomCandidateNumbers($raffle, $candidatePoolSize);

            $available = RaffleNumber::query()
                ->where('raffle_id', $raffle->id)
                ->where('status', 'available')
                ->whereIn('number', $candidates)
                ->limit($quantity - $selected->count())
                ->pluck('number');

            $selected = $selected->merge($available)->unique()->values();
        }

        if ($selected->count() < $quantity) {
            $fallback = RaffleNumber::query()
                ->where('raffle_id', $raffle->id)
                ->where('status', 'available')
                ->orderBy('id')
                ->limit($quantity - $selected->count())
                ->pluck('number');

            $selected = $selected->merge($fallback)->unique()->values();
        }

        return $selected->take($quantity)->all();
    }

    public function approximateRandomNumbers(Raffle $raffle, int $packageCount): array
    {
        $quantity = $this->quantityFor($raffle, $packageCount);

        return $this->randomCandidateNumbers($raffle, $quantity);
    }

    public function reserve(Raffle $raffle, array $buyer, array $numbers, array $receipt, int $packageCount, int $randomChangesUsed = 0, string $selectionSource = 'manual'): Order
    {
        $expectedQuantity = $this->quantityFor($raffle, $packageCount);
        $numbers = $this->normalizeNumbers($numbers);
        $selectionSource = $this->normalizeSelectionSource($selectionSource);

        if (count($numbers) !== $expectedQuantity) {
            throw new RuntimeException("Esta compra debe tener {$expectedQuantity} numero(s).");
        }

        $order = DB::transaction(function () use ($raffle, $buyer, $numbers, $receipt, $packageCount, $randomChangesUsed, $selectionSource, $expectedQuantity) {
            $lockedRaffle = Raffle::query()->whereKey($raffle->id)->lockForUpdate()->firstOrFail();

            if (! $lockedRaffle->sale_enabled) {
                throw new RuntimeException('La venta de esta rifa esta pausada.');
            }

            $this->releaseExpiredReservationsIfDue($lockedRaffle);

            $lockedNumbers = RaffleNumber::query()
                ->where('raffle_id', $lockedRaffle->id)
                ->whereIn('number', $numbers)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedNumbers->count() !== count($numbers)) {
                throw new RuntimeException('Uno o mas numeros no existen en esta rifa.');
            }

            if ($lockedNumbers->contains(fn (RaffleNumber $number) => $number->status !== 'available')) {
                if ($selectionSource !== 'random') {
                    throw new RuntimeException('Uno o mas numeros ya no estan disponibles. Intenta con otra seleccion.');
                }

                $lockedNumbers = $this->availableReplacementNumbers($lockedRaffle, $expectedQuantity, $numbers);

                if ($lockedNumbers->count() !== $expectedQuantity) {
                    throw new RuntimeException('Uno o mas numeros ya no estan disponibles. Intenta con otra seleccion.');
                }
            }

            $order = Order::create([
                'public_uuid' => (string) Str::uuid(),
                'raffle_id' => $lockedRaffle->id,
                'buyer_name' => $buyer['name'],
                'buyer_phone' => $buyer['phone'],
                'buyer_email' => $buyer['email'] ?? null,
                'package_count' => $packageCount,
                'amount_total' => $lockedRaffle->price_per_package * $packageCount,
                'assignment_mode' => $selectionSource,
                'random_changes_used' => $randomChangesUsed,
                'status' => 'pending',
                'receipt_path' => $receipt['path'] ?? null,
                'receipt_original_name' => $receipt['original_name'] ?? null,
                'receipt_mime' => $receipt['mime'] ?? null,
            ]);

            $reservedUntil = now()->addMinutes($lockedRaffle->reservation_minutes);

            foreach ($lockedNumbers as $raffleNumber) {
                $raffleNumber->forceFill([
                    'status' => 'reserved',
                    'reserved_until' => $reservedUntil,
                ])->save();

                $order->numbers()->attach($raffleNumber->id, ['number' => $raffleNumber->number]);
            }

            return $order->load('numbers', 'raffle');
        }, 5);

        app(PublicRaffleSnapshotService::class)->adjustCounts($order->raffle, availableDelta: -$order->numbers->count(), reservedDelta: $order->numbers->count());

        return $order;
    }

    public function releaseExpiredReservations(Raffle $raffle): int
    {
        return RaffleNumber::query()
            ->where('raffle_id', $raffle->id)
            ->where('status', 'reserved')
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<', now())
            ->update([
                'status' => 'available',
                'reserved_until' => null,
                'updated_at' => now(),
            ]);
    }

    private function randomCandidateNumbers(Raffle $raffle, int $count): array
    {
        $numbers = [];
        $end = $raffle->numberEnd();

        while (count($numbers) < $count) {
            $numbers[$raffle->formatNumber(random_int($raffle->numberStart(), $end))] = true;
        }

        return array_keys($numbers);
    }

    private function releaseExpiredReservationsIfDue(Raffle $raffle): void
    {
        if (random_int(1, 30) !== 1) {
            return;
        }

        $this->releaseExpiredReservations($raffle);
    }

    private function normalizeNumbers(array $numbers): array
    {
        return collect($numbers)
            ->map(fn ($number) => trim((string) $number))
            ->filter(fn (string $number) => $number !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function availableReplacementNumbers(Raffle $raffle, int $quantity, array $excludedNumbers): Collection
    {
        $selected = collect();
        $candidatePoolSize = min($raffle->total_numbers, max($quantity * 12, 80));

        for ($attempt = 0; $attempt < 5 && $selected->count() < $quantity; $attempt++) {
            $candidates = $this->randomCandidateNumbers($raffle, $candidatePoolSize);

            $available = RaffleNumber::query()
                ->where('raffle_id', $raffle->id)
                ->where('status', 'available')
                ->whereNotIn('number', $excludedNumbers)
                ->whereIn('number', $candidates)
                ->orderBy('id')
                ->lockForUpdate()
                ->limit($quantity - $selected->count())
                ->get();

            $selected = $selected->merge($available)->unique('id')->values();
        }

        if ($selected->count() < $quantity) {
            $fallback = RaffleNumber::query()
                ->where('raffle_id', $raffle->id)
                ->where('status', 'available')
                ->whereNotIn('number', $excludedNumbers)
                ->orderBy('id')
                ->lockForUpdate()
                ->limit($quantity - $selected->count())
                ->get();

            $selected = $selected->merge($fallback)->unique('id')->values();
        }

        return $selected->take($quantity);
    }

    private function normalizeSelectionSource(string $selectionSource): string
    {
        return $selectionSource === 'random' ? 'random' : 'manual';
    }

    private function quantityFor(Raffle $raffle, int $packageCount): int
    {
        if ($packageCount < 1 || $packageCount > 5) {
            throw new RuntimeException('Selecciona una cantidad valida.');
        }

        return $raffle->numbers_per_package * $packageCount;
    }
}
