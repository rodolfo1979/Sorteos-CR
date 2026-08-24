<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $processedQuery = Order::with('raffle', 'numbers')
            ->whereIn('status', ['approved', 'rejected'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('buyer_name', 'like', "%{$search}%")
                        ->orWhere('buyer_phone', 'like', "%{$search}%")
                        ->orWhere('buyer_email', 'like', "%{$search}%")
                        ->orWhere('public_uuid', 'like', "%{$search}%")
                        ->orWhereHas('raffle', fn ($raffleQuery) => $raffleQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('numbers', fn ($numberQuery) => $numberQuery->where('raffle_numbers.number', 'like', "%{$search}%"));
                });
            })
            ->latest('updated_at');

        return view('admin.payments.index', [
            'pendingOrders' => Order::with('raffle', 'numbers')->where('status', 'pending')->latest()->get(),
            'processedOrders' => $processedQuery->paginate(10)->withQueryString(),
            'search' => $search,
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

        $mailSent = $mailService->sendApproved($order->fresh(['raffle', 'numbers']));

        return back()->with('status', $mailSent
            ? 'Pago aprobado. Los numeros quedaron vendidos y el correo fue enviado al cliente.'
            : 'Pago aprobado. Los numeros quedaron vendidos, pero no se pudo enviar el correo al cliente. Revisa la configuracion SMTP o el correo del comprador.');
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

        $mailSent = $mailService->sendRejected($order->fresh(['raffle', 'numbers']));

        return back()->with('status', $mailSent
            ? 'Pago rechazado. Los numeros volvieron a estar disponibles y el correo fue enviado al cliente.'
            : 'Pago rechazado. Los numeros volvieron a estar disponibles, pero no se pudo enviar el correo al cliente. Revisa la configuracion SMTP o el correo del comprador.');
    }
}
