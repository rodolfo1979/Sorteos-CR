<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Raffle;
use App\Models\RaffleNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_realtime_endpoint_is_protected(): void
    {
        $this->getJson(route('admin.realtime'))->assertUnauthorized();
    }

    public function test_admin_realtime_endpoint_returns_pending_orders_and_stats(): void
    {
        $raffle = Raffle::create([
            'name' => 'Rifa en vivo',
            'slug' => 'rifa-en-vivo',
            'total_numbers' => 10000,
            'number_width' => 4,
            'price_per_package' => 4000,
            'numbers_per_package' => 2,
            'max_random_changes' => 5,
            'reservation_minutes' => 45,
            'assignment_mode' => 'manual',
            'sale_enabled' => true,
            'is_featured' => true,
            'organizer_name' => 'Sorteos CR',
        ]);

        $numbers = collect(['0001', '0002'])->map(fn (string $number) => RaffleNumber::create([
            'raffle_id' => $raffle->id,
            'number' => $number,
            'status' => 'reserved',
            'reserved_until' => now()->addMinutes(45),
        ]));

        $order = Order::create([
            'public_uuid' => (string) Str::uuid(),
            'raffle_id' => $raffle->id,
            'buyer_name' => 'Cliente En Vivo',
            'buyer_phone' => '88888888',
            'buyer_email' => 'cliente@example.com',
            'package_count' => 1,
            'amount_total' => 4000,
            'assignment_mode' => 'manual',
            'status' => 'pending',
            'receipt_path' => 'receipts/prueba.jpg',
        ]);

        foreach ($numbers as $number) {
            $order->numbers()->attach($number->id, ['number' => $number->number]);
        }

        $this->withSession(['admin_authenticated' => true])
            ->getJson(route('admin.realtime'))
            ->assertOk()
            ->assertJsonPath('stats.pending_payments', 1)
            ->assertJsonPath('stats.reserved_numbers', '2')
            ->assertJsonPath('pending_orders.0.buyer_name', 'Cliente En Vivo')
            ->assertJsonPath('pending_orders.0.numbers.0', '0001')
            ->assertJsonPath('recent_orders.0.status', 'pending');
    }
}