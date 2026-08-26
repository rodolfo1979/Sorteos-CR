<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderActivityService
{
    public function record(Order $order, string $action, string $description, array $metadata = [], ?string $actor = null): void
    {
        try {
            $order->activityEvents()->create([
                'action' => $action,
                'actor' => $actor ?: $this->actor(),
                'description' => $description,
                'metadata' => $metadata === [] ? null : $metadata,
            ]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo registrar evento administrativo de orden.', [
                'order_id' => $order->id,
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function actor(): string
    {
        $request = request();

        if ($request && $request->is('admin*')) {
            return 'admin:'.($request->getUser() ?: 'basic_auth');
        }

        return 'system';
    }
}