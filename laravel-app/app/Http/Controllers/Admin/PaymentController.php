<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        return view('admin.payments.index', [
            'orders' => Order::with('raffle', 'numbers')->where('status', 'pending')->latest()->get(),
        ]);
    }

    public function approve(Order $order, OrderMailService $mailService): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $order->load('numbers');
            foreach ($order->numbers as $number) {
                $number->update(['status' => 'sold', 'reserved_until' => null]);
            }
            $order->update(['status' => 'approved', 'approved_at' => now()]);
        });

        $mailService->sendApproved($order);

        return back()->with('status', 'Pago aprobado. Los numeros quedaron vendidos.');
    }

    public function reject(Order $order, OrderMailService $mailService): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $order->load('numbers');
            foreach ($order->numbers as $number) {
                $number->update(['status' => 'available', 'reserved_until' => null]);
            }
            $order->update(['status' => 'rejected', 'rejected_at' => now()]);
        });

        $mailService->sendRejected($order);

        return back()->with('status', 'Pago rechazado. Los numeros volvieron a estar disponibles.');
    }
}

