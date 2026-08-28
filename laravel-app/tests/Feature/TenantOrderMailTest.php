<?php

namespace Tests\Feature;

use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\Raffle;
use App\Models\Tenant;
use App\Services\OrderMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantOrderMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_new_order_notification_uses_tenant_notification_email_and_sender(): void
    {
        Mail::fake();

        $order = $this->tenantOrder();

        app(OrderMailService::class)->notifyAdminNewOrder($order, 'test');

        Mail::assertQueued(OrderStatusMail::class, function (OrderStatusMail $mail): bool {
            $mail->build();

            return $mail->hasTo('avisos@ganadores.test')
                && $mail->hasFrom('ventas@ganadores.test', 'Ganadores CR');
        });

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'action' => 'admin_email_queued',
        ]);
    }

    public function test_buyer_order_email_uses_tenant_sender(): void
    {
        Mail::fake();

        $order = $this->tenantOrder();

        app(OrderMailService::class)->sendApproved($order, 'test');

        Mail::assertSent(OrderStatusMail::class, function (OrderStatusMail $mail): bool {
            $mail->build();

            return $mail->hasTo('comprador@example.com')
                && $mail->hasFrom('ventas@ganadores.test', 'Ganadores CR');
        });
    }

    private function tenantOrder(): Order
    {
        $tenant = Tenant::query()->create([
            'name' => 'Ganadores CR',
            'slug' => 'ganadores-cr',
            'status' => 'active',
            'primary_domain' => 'ganadores.example.com',
            'admin_email' => 'admin@ganadores.test',
            'notification_email' => 'fallback@ganadores.test',
            'timezone' => 'America/Costa_Rica',
            'currency' => 'CRC',
        ]);

        $tenant->settings()->create([
            'mail_from_address' => 'ventas@ganadores.test',
            'mail_from_name' => 'Ganadores CR',
            'notification_email' => 'avisos@ganadores.test',
            'reservation_minutes_default' => 45,
        ]);

        $raffle = Raffle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'HILUX 2017 4X4',
            'slug' => 'hilux-2017-4x4',
            'total_numbers' => 10000,
            'number_width' => 4,
            'price_per_package' => 4000,
            'numbers_per_package' => 2,
            'max_random_changes' => 5,
            'reservation_minutes' => 45,
            'assignment_mode' => 'manual',
            'sale_enabled' => true,
            'is_featured' => true,
            'organizer_name' => 'Ganadores CR',
        ]);

        return Order::query()->create([
            'tenant_id' => $tenant->id,
            'public_uuid' => (string) Str::uuid(),
            'raffle_id' => $raffle->id,
            'buyer_name' => 'Comprador Demo',
            'buyer_phone' => '88888888',
            'buyer_email' => 'comprador@example.com',
            'package_count' => 1,
            'amount_total' => 4000,
            'assignment_mode' => 'manual',
            'status' => 'pending',
        ]);
    }
}
