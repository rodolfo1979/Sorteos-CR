<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdminPaymentController extends Controller
{
    public function approve(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $order->load('raffleNumbers');

            if ($order->status !== 'pending') {
                return;
            }

            foreach ($order->raffleNumbers as $number) {
                $number->update([
                    'status' => 'sold',
                    'reserved_until' => null,
                ]);
            }

            $order->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            // Enviar correo con cola:
            // Mail::to($order->buyer_email)->queue(new PaymentApprovedMail($order));
        });

        return back()->with('status', 'Pago aprobado y numeros vendidos.');
    }

    public function reject(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $order->load('raffleNumbers');

            if ($order->status !== 'pending') {
                return;
            }

            foreach ($order->raffleNumbers as $number) {
                $number->update([
                    'status' => 'available',
                    'order_id' => null,
                    'reserved_until' => null,
                ]);
            }

            $order->update([
                'status' => 'rejected',
                'rejected_at' => now(),
            ]);
        });

        return back()->with('status', 'Pago rechazado y numeros liberados.');
    }
}
