<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderMailService
{
    public function sendReserved(Order $order): void
    {
        $order->loadMissing('raffle', 'numbers');

        $this->sendToBuyer(
            $order,
            'emails.order-reserved',
            'Tus tickets fueron reservados - '.$order->raffle->name,
            'reserva'
        );
    }

    public function sendApproved(Order $order): void
    {
        $order->loadMissing('raffle', 'numbers');

        $this->sendToBuyer(
            $order,
            'emails.order-approved',
            'Pago validado - '.$order->raffle->name,
            'aprobacion'
        );
    }

    public function sendRejected(Order $order): void
    {
        $order->loadMissing('raffle', 'numbers');

        $this->sendToBuyer(
            $order,
            'emails.order-rejected',
            'Compra rechazada - '.$order->raffle->name,
            'rechazo'
        );
    }

    public function notifyAdminNewOrder(Order $order): void
    {
        $adminEmail = config('admin.notification_email');

        if (! $adminEmail) {
            return;
        }

        $order->loadMissing('raffle', 'numbers');

        try {
            Mail::send('emails.admin-new-order', ['order' => $order], function ($message) use ($order, $adminEmail) {
                $message->to($adminEmail)
                    ->subject('Nuevo comprobante pendiente - '.$order->raffle->name);
            });
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar notificacion admin de compra.', [
                'order_id' => $order->id,
                'admin_email' => $adminEmail,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendToBuyer(Order $order, string $view, string $subject, string $type): void
    {
        if (! $order->buyer_email) {
            return;
        }

        try {
            Mail::send($view, ['order' => $order], function ($message) use ($order, $subject) {
                $message->to($order->buyer_email, $order->buyer_name)
                    ->subject($subject);
            });
        } catch (Throwable $exception) {
            Log::warning("No se pudo enviar correo de {$type}.", [
                'order_id' => $order->id,
                'buyer_email' => $order->buyer_email,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
