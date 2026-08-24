<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Raffle;
use App\Models\RaffleNumber;
use App\Services\RaffleReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class RaffleReservationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_rejects_duplicate_numbers_in_same_purchase(): void
    {
        $raffle = $this->raffle();
        $this->number($raffle, '0001');
        $this->number($raffle, '0002');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Esta compra debe tener 2 numero(s).');

        app(RaffleReservationService::class)->reserve(
            $raffle,
            $this->buyer(),
            ['0001', '0001'],
            ['path' => 'receipts/test.jpg'],
            1,
        );
    }

    public function test_reservation_rejects_numbers_that_are_no_longer_available(): void
    {
        $raffle = $this->raffle();
        $this->number($raffle, '0001', 'sold');
        $this->number($raffle, '0002');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Uno o mas numeros ya no estan disponibles. Intenta con otra seleccion.');

        app(RaffleReservationService::class)->reserve(
            $raffle,
            $this->buyer(),
            ['0001', '0002'],
            ['path' => 'receipts/test.jpg'],
            1,
        );
    }

    public function test_purchase_availability_conflict_returns_soft_notice_instead_of_global_error(): void
    {
        Storage::fake('public');
        $raffle = $this->raffle();
        $this->number($raffle, '0001', 'sold');
        $this->number($raffle, '0002');

        $this->post(route('purchases.store', $raffle), [
            'buyer_name' => 'Cliente Seguro',
            'buyer_phone' => '88888888',
            'buyer_email' => 'cliente@example.com',
            'package_count' => 1,
            'numbers' => ['0001', '0002'],
            'receipt' => UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf'),
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('availability_notice', 'Actualizamos la disponibilidad. Elige nuevamente tus numeros y adjunta el comprobante para continuar.');

        $this->assertSame(0, Order::count());
        $this->assertCount(0, Storage::disk('public')->files('receipts'));
    }

    public function test_expired_reserved_numbers_can_be_released_by_maintenance(): void
    {
        $raffle = $this->raffle();
        $this->number($raffle, '0001', 'reserved', now()->subMinute());
        $this->number($raffle, '0002', 'reserved', now()->addHour());
        $this->number($raffle, '0003');

        app(RaffleReservationService::class)->releaseExpiredReservations($raffle);

        $this->assertSame('available', RaffleNumber::where('number', '0001')->first()->status);
        $this->assertSame('reserved', RaffleNumber::where('number', '0002')->first()->status);
    }

    public function test_successful_reservation_locks_numbers_as_reserved_and_creates_single_order(): void
    {
        $raffle = $this->raffle();
        $this->number($raffle, '0001');
        $this->number($raffle, '0002');

        $order = app(RaffleReservationService::class)->reserve(
            $raffle,
            $this->buyer(),
            [' 0001 ', '0002'],
            ['path' => 'receipts/test.jpg'],
            1,
        );

        $this->assertSame('pending', $order->status);
        $this->assertSame(1, Order::count());
        $this->assertSame(2, RaffleNumber::where('status', 'reserved')->count());
    }

    private function raffle(): Raffle
    {
        return Raffle::create([
            'name' => 'Rifa Segura',
            'slug' => 'rifa-segura-'.str()->random(6),
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
    }

    private function number(Raffle $raffle, string $number, string $status = 'available', mixed $reservedUntil = null): RaffleNumber
    {
        return RaffleNumber::create([
            'raffle_id' => $raffle->id,
            'number' => $number,
            'status' => $status,
            'reserved_until' => $reservedUntil,
        ]);
    }

    private function buyer(): array
    {
        return [
            'name' => 'Cliente Seguro',
            'phone' => '88888888',
            'email' => 'cliente@example.com',
        ];
    }
}