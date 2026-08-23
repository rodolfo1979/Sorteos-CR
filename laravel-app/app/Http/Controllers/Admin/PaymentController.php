<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        return view('admin.payments.index', [
            'orders' => Order::with('raffle', 'numbers')->where('status', 'pending')->latest()->get(),
        ]);
    }

    public function approve(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $order->load('numbers');
            foreach ($order->numbers as $number) {
                $number->update(['status' => 'sold', 'reserved_until' => null]);
            }
            $order->update(['status' => 'approved', 'approved_at' => now()]);
        });

        $order->load('raffle', 'numbers');
        if ($order->buyer_email) {
            try {
                Mail::send('emails.order-approved', ['order' => $order], function ($message) use ($order) {
                    $message->to($order->buyer_email, $order->buyer_name)
                        ->subject('Pago validado - '.$order->raffle->name);
                });
            } catch (Throwable $exception) {
                Log::warning('No se pudo enviar correo de aprobacion.', ['order_id' => $order->id, 'error' => $exception->getMessage()]);
            }
        }

        return back()->with('status', 'Pago aprobado. Los numeros quedaron vendidos.');
    }

    public function reject(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $order->load('numbers');
            foreach ($order->numbers as $number) {
                $number->update(['status' => 'available', 'reserved_until' => null]);
            }
            $order->update(['status' => 'rejected', 'rejected_at' => now()]);
        });

        return back()->with('status', 'Pago rechazado. Los numeros volvieron a estar disponibles.');
    }
}

