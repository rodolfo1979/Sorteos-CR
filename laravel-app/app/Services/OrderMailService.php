<?php

namespace App\Services;

use App\Mail\OrderStatusMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderMailService
{
    public function sendReserved(Order $order, string $source = 'automatic'): bool
    {
        $order->loadMissing('raffle', 'numbers');

        return $this->sendToBuyer(
            $order,
            'emails.order-reserved',
            'Tus tickets fueron reservados - '.$order->raffle->name,
            'reserva',
            $source
        );
    }

    public function sendApproved(Order $order, string $source = 'automatic'): bool
    {
        $order->loadMissing('raffle', 'numbers');

        return $this->sendToBuyerNow(
            $order,
            'emails.order-approved',
            'Pago validado - '.$order->raffle->name,
            'aprobacion',
            $source
        );
    }

    public function sendRejected(Order $order, string $source = 'automatic'): bool
    {
        $order->loadMissing('raffle', 'numbers');

        return $this->sendToBuyerNow(
            $order,
            'emails.order-rejected',
            'Compra rechazada - '.$order->raffle->name,
            'rechazo',
            $source
        );
    }

    public function notifyAdminNewOrder(Order $order, string $source = 'automatic'): void
    {
        $adminEmail = config('admin.notification_email');

        if (! $adminEmail) {
            app(OrderActivityService::class)->record($order, 'admin_email_skipped', 'No se envio aviso admin porque no hay correo configurado.', [
                'source' => $source,
            ]);

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

            app(OrderActivityService::class)->record($order, 'admin_email_queued', 'Aviso al admin encolado.', [
                'source' => $source,
                'to' => $adminEmail,
                'subject' => $subject,
            ]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar notificacion admin de compra.', [
                'order_id' => $order->id,
                'admin_email' => $adminEmail,
                'error' => $exception->getMessage(),
            ]);

            app(OrderActivityService::class)->record($order, 'admin_email_failed', 'Fallo el aviso al admin.', [
                'source' => $source,
                'to' => $adminEmail,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendToBuyer(Order $order, string $view, string $subject, string $type, string $source): bool
    {
        if (! $order->buyer_email) {
            Log::warning("No se envio correo de {$type} porque la orden no tiene correo del comprador.", [
                'order_id' => $order->id,
                'buyer_name' => $order->buyer_name,
            ]);

            app(OrderActivityService::class)->record($order, 'buyer_email_skipped', "No se envio correo de {$type}: comprador sin correo.", [
                'source' => $source,
                'type' => $type,
                'subject' => $subject,
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

            app(OrderActivityService::class)->record($order, 'buyer_email_queued', "Correo de {$type} encolado para comprador.", [
                'source' => $source,
                'type' => $type,
                'to' => $order->buyer_email,
                'subject' => $subject,
            ]);

            return true;
        } catch (Throwable $exception) {
            Log::warning("No se pudo enviar correo de {$type}.", [
                'order_id' => $order->id,
                'buyer_email' => $order->buyer_email,
                'error' => $exception->getMessage(),
            ]);

            app(OrderActivityService::class)->record($order, 'buyer_email_failed', "Fallo el correo de {$type} para comprador.", [
                'source' => $source,
                'type' => $type,
                'to' => $order->buyer_email,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendToBuyerNow(Order $order, string $view, string $subject, string $type, string $source): bool
    {
        if (! $order->buyer_email) {
            Log::warning("No se envio correo de {$type} porque la orden no tiene correo del comprador.", [
                'order_id' => $order->id,
                'buyer_name' => $order->buyer_name,
            ]);

            app(OrderActivityService::class)->record($order, 'buyer_email_skipped', "No se envio correo de {$type}: comprador sin correo.", [
                'source' => $source,
                'type' => $type,
                'subject' => $subject,
            ]);

            return false;
        }

        try {
            Mail::to($order->buyer_email, $order->buyer_name)->send(new OrderStatusMail($order, $view, $subject));

            Log::warning("Correo enviado directamente para comprador: {$type}.", [
                'type' => $type,
                'order_id' => $order->id,
                'email' => $order->buyer_email,
                'subject' => $subject,
            ]);

            app(OrderActivityService::class)->record($order, 'buyer_email_sent', "Correo de {$type} enviado directamente al comprador.", [
                'source' => $source,
                'type' => $type,
                'to' => $order->buyer_email,
                'subject' => $subject,
            ]);

            return true;
        } catch (Throwable $exception) {
            Log::warning("No se pudo enviar correo directo de {$type}.", [
                'order_id' => $order->id,
                'buyer_email' => $order->buyer_email,
                'error' => $exception->getMessage(),
            ]);

            app(OrderActivityService::class)->record($order, 'buyer_email_failed', "Fallo el correo directo de {$type} para comprador.", [
                'source' => $source,
                'type' => $type,
                'to' => $order->buyer_email,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}