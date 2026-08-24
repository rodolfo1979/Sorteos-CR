<?php

namespace Tests\Feature;

use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\Raffle;
use App\Models\RaffleNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentRejectionEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejecting_a_payment_sends_rejection_email_and_releases_numbers(): void
    {
        Mail::fake();
        config(['admin.username' => 'admin-test', 'admin.password' => 'secure-test-password']);

        $raffle = Raffle::create([
            'name' => 'Rifa de prueba',
            'slug' => 'rifa-de-prueba',
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
            'buyer_name' => 'Cliente Prueba',
            'buyer_phone' => '88888888',
            'buyer_email' => 'cliente@example.com',
            'package_count' => 1,
            'amount_total' => 4000,
            'assignment_mode' => 'manual',
            'status' => 'pending',
        ]);

        foreach ($numbers as $number) {
            $order->numbers()->attach($number->id, ['number' => $number->number]);
        }

        $this->withBasicAuth('admin-test', 'secure-test-password')
            ->post(route('admin.payments.reject', $order))
            ->assertRedirect()
            ->assertSessionHas('status', 'Pago rechazado. Los numeros volvieron a estar disponibles y el correo fue enviado al cliente.');

        $this->assertSame('rejected', $order->fresh()->status);
        $this->assertTrue($order->fresh()->rejected_at !== null);
        $this->assertSame(2, RaffleNumber::where('raffle_id', $raffle->id)->where('status', 'available')->count());
        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $message) => $message->hasTo('cliente@example.com'));
    }
}
