<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Raffle;
use App\Models\RaffleNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderEventsBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_command_creates_basic_events_without_duplicates(): void
    {
        $raffle = Raffle::create([
            'name' => 'Rifa historica',
            'slug' => 'rifa-historica',
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
            'status' => 'sold',
        ]));

        $order = Order::create([
            'public_uuid' => (string) Str::uuid(),
            'raffle_id' => $raffle->id,
            'buyer_name' => 'Cliente Historico',
            'buyer_phone' => '88888888',
            'buyer_email' => 'cliente@example.com',
            'package_count' => 1,
            'amount_total' => 4000,
            'assignment_mode' => 'manual',
            'status' => 'approved',
            'approved_at' => now()->subHour(),
        ]);

        foreach ($numbers as $number) {
            $order->numbers()->attach($number->id, ['number' => $number->number]);
        }

        $this->artisan('orders:backfill-events')
            ->expectsOutput('Eventos administrativos reconstruidos: 2.')
            ->assertSuccessful();

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'action' => 'order_created',
            'actor' => 'system:backfill',
        ]);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'action' => 'payment_approved',
            'actor' => 'system:backfill',
        ]);

        $this->artisan('orders:backfill-events')
            ->expectsOutput('Eventos administrativos reconstruidos: 0.')
            ->assertSuccessful();

        $this->assertSame(2, $order->activityEvents()->count());
    }
}