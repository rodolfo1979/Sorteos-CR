<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\RaffleNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireReservationsCommand extends Command
{
    protected $signature = 'raffles:expire-reservations';

    protected $description = 'Libera numeros reservados cuyo tiempo expiro.';

    public function handle(): int
    {
        $expiredOrderIds = RaffleNumber::query()
            ->where('status', 'reserved')
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<', now())
            ->whereNotNull('order_id')
            ->pluck('order_id')
            ->unique()
            ->values();

        foreach ($expiredOrderIds as $orderId) {
            DB::transaction(function () use ($orderId) {
                $order = Order::query()
                    ->whereKey($orderId)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    return;
                }

                RaffleNumber::query()
                    ->where('order_id', $order->id)
                    ->where('status', 'reserved')
                    ->lockForUpdate()
                    ->update([
                        'status' => 'available',
                        'order_id' => null,
                        'reserved_until' => null,
                    ]);

                $order->update(['status' => 'expired']);
            }, 3);
        }

        $this->info("Reservas vencidas procesadas: {$expiredOrderIds->count()}");

        return self::SUCCESS;
    }
}
