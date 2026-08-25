<?php

namespace App\Services;

use App\Mail\OrderStatusMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderMailService
{
    public function sendReserved(Order $order): bool
    {
        $order->loadMissing('raffle', 'numbers');

        return $this->sendToBuyer(
            $order,
            'emails.order-reserved',
            'Tus tickets fueron reservados - '.$order->raffle->name,
            'reserva'
        );
    }

    public function sendApproved(Order $order): bool
    {
        $order->loadMissing('raffle', 'numbers');

        return $this->sendToBuyer(
            $order,
            'emails.order-approved',
            'Pago validado - '.$order->raffle->name,
            'aprobacion'
        );
    }

    public function sendRejected(Order $order): bool
    {
        $order->loadMissing('raffle', 'numbers');

        return $this->sendToBuyer(
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

        $subject = 'Nuevo comprobante pendiente - '.$order->raffle->name;

        try {
            Mail::to($adminEmail)->queue(new OrderStatusMail(
                $order,
                'emails.admin-new-order',
                $subject
            ));

            Log::warning('Correo encolado para administracion.', [
                'type' => 'admin_nueva_compra',
                'order_id' => $order->id,
                'email' => $adminEmail,
                'subject' => $subject,
            ]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar notificacion admin de compra.', [
                'order_id' => $order->id,
                'admin_email' => $adminEmail,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendToBuyer(Order $order, string $view, string $subject, string $type): bool
    {
        if (! $order->buyer_email) {
            Log::warning("No se envio correo de {$type} porque la orden no tiene correo del comprador.", [
                'order_id' => $order->id,
                'buyer_name' => $order->buyer_name,
            ]);

            return false;
        }

        try {
            Mail::to($order->buyer_email, $order->buyer_name)->queue(new OrderStatusMail($order, $view, $subject));

            Log::warning("Correo encolado para comprador: {$type}.", [
                'type' => $type,
                'order_id' => $order->id,
                'email' => $order->buyer_email,
                'subject' => $subject,
            ]);

            return true;
        } catch (Throwable $exception) {
            Log::warning("No se pudo enviar correo de {$type}.", [
                'order_id' => $order->id,
                'buyer_email' => $order->buyer_email,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
