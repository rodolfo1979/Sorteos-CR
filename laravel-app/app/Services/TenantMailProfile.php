<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Tenant;

class TenantMailProfile
{
    public function forOrder(Order $order): array
    {
        $tenant = $this->tenantForOrder($order);

        return [
            'from_address' => $this->fromAddress($tenant),
            'from_name' => $this->fromName($tenant),
            'notification_email' => $this->notificationEmail($tenant),
        ];
    }

    public function tenantForOrder(Order $order): ?Tenant
    {
        $order->loadMissing('tenant.settings', 'raffle.tenant.settings');

        return $order->tenant ?: $order->raffle?->tenant;
    }

    public function fromAddress(?Tenant $tenant): string
    {
        return (string) (
            $tenant?->settings?->mail_from_address
            ?: config('mail.from.address')
        );
    }

    public function fromName(?Tenant $tenant): string
    {
        return (string) (
            $tenant?->settings?->mail_from_name
            ?: $tenant?->name
            ?: config('mail.from.name')
        );
    }

    public function notificationEmail(?Tenant $tenant): ?string
    {
        return $tenant?->settings?->notification_email
            ?: $tenant?->notification_email
            ?: $tenant?->admin_email
            ?: config('admin.notification_email');
    }
}
