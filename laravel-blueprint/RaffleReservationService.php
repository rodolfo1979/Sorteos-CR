<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Raffle;
use App\Models\RaffleNumber;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RaffleReservationService
{
    public function reserveManual(Raffle $raffle, array $numbers, array $buyer, ?string $receiptPath): Order
    {
        $this->ensureSaleIsOpen($raffle);

        if (count($numbers) !== $raffle->numbers_per_order) {
            throw new RuntimeException('Cantidad de numeros invalida.');
        }

        return DB::transaction(function () use ($raffle, $numbers, $buyer, $receiptPath) {
            $rows = RaffleNumber::query()
                ->where('raffle_id', $raffle->id)
                ->whereIn('number', $numbers)
                ->where('status', 'available')
                ->lockForUpdate()
                ->get();

            if ($rows->count() !== count($numbers)) {
                throw new RuntimeException('Uno o mas numeros ya no estan disponibles.');
            }

            return $this->createPendingOrder($raffle, $rows, $buyer, $receiptPath, 1);
        }, 3);
    }

    public function reserveRandom(Raffle $raffle, array $buyer, ?string $receiptPath, int $packageCount = 1): Order
    {
        $this->ensureSaleIsOpen($raffle);

        $quantity = $raffle->numbers_per_order * $packageCount;

        return DB::transaction(function () use ($raffle, $buyer, $receiptPath, $packageCount, $quantity) {
            // En MySQL funciona bien para volumen moderado. Para volumen alto, usar estrategia con ids aleatorios prefiltrados.
            $rows = RaffleNumber::query()
                ->where('raffle_id', $raffle->id)
                ->where('status', 'available')
                ->inRandomOrder()
                ->limit($quantity)
                ->lockForUpdate()
                ->get();

            if ($rows->count() !== $quantity) {
                throw new RuntimeException('No hay suficientes numeros disponibles.');
            }

            return $this->createPendingOrder($raffle, $rows, $buyer, $receiptPath, $packageCount);
        }, 3);
    }

    private function createPendingOrder(Raffle $raffle, $rows, array $buyer, ?string $receiptPath, int $packageCount): Order
    {
        $order = Order::create([
            'raffle_id' => $raffle->id,
            'buyer_name' => $buyer['name'],
            'buyer_phone' => $buyer['phone'],
            'buyer_email' => $buyer['email'] ?? null,
            'amount' => $raffle->price * $packageCount,
            'package_count' => $packageCount,
            'assignment_mode' => $raffle->assignment_mode,
            'status' => 'pending',
            'receipt_path' => $receiptPath,
        ]);

        $reservedUntil = now()->addMinutes($raffle->reservation_minutes ?? 45);

        foreach ($rows as $row) {
            $row->update([
                'status' => 'reserved',
                'order_id' => $order->id,
                'reserved_until' => $reservedUntil,
            ]);

            $order->numbers()->create([
                'raffle_number_id' => $row->id,
                'number' => $row->number,
            ]);
        }

        return $order;
    }

    private function ensureSaleIsOpen(Raffle $raffle): void
    {
        if (!$raffle->sale_enabled) {
            throw new RuntimeException('La venta de esta rifa esta pausada.');
        }
    }
}
