<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Raffle;
use App\Models\RaffleNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RaffleReservationService
{
    public function randomNumbers(Raffle $raffle, int $packageCount): array
    {
        $quantity = $this->quantityFor($raffle, $packageCount);
        $this->releaseExpiredReservations($raffle);

        return RaffleNumber::query()
            ->where('raffle_id', $raffle->id)
            ->where('status', 'available')
            ->inRandomOrder()
            ->limit($quantity)
            ->pluck('number')
            ->all();
    }

    public function reserve(Raffle $raffle, array $buyer, array $numbers, array $receipt, int $packageCount, int $randomChangesUsed = 0): Order
    {
        $expectedQuantity = $this->quantityFor($raffle, $packageCount);
        $numbers = $this->normalizeNumbers($numbers);

        if (count($numbers) !== $expectedQuantity) {
            throw new RuntimeException("Esta compra debe tener {$expectedQuantity} numero(s).");
        }

        return DB::transaction(function () use ($raffle, $buyer, $numbers, $receipt, $packageCount, $randomChangesUsed) {
            $lockedRaffle = Raffle::query()->whereKey($raffle->id)->lockForUpdate()->firstOrFail();

            if (! $lockedRaffle->sale_enabled) {
                throw new RuntimeException('La venta de esta rifa esta pausada.');
            }

            $this->releaseExpiredReservations($lockedRaffle);

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
                throw new RuntimeException('Uno o mas numeros ya no estan disponibles. Intenta con otra seleccion.');
            }

            $order = Order::create([
                'public_uuid' => (string) Str::uuid(),
                'raffle_id' => $lockedRaffle->id,
                'buyer_name' => $buyer['name'],
                'buyer_phone' => $buyer['phone'],
                'buyer_email' => $buyer['email'] ?? null,
                'package_count' => $packageCount,
                'amount_total' => $lockedRaffle->price_per_package * $packageCount,
                'assignment_mode' => $lockedRaffle->assignment_mode,
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

    private function normalizeNumbers(array $numbers): array
    {
        return collect($numbers)
            ->map(fn ($number) => trim((string) $number))
            ->filter(fn (string $number) => $number !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function quantityFor(Raffle $raffle, int $packageCount): int
    {
        if ($packageCount < 1 || $packageCount > 5) {
            throw new RuntimeException('Selecciona una cantidad valida.');
        }

        return $raffle->numbers_per_package * $packageCount;
    }
}
