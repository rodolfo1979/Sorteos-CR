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
        $processed = DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== 'pending') {
                return false;
            }

            $lockedOrder->numbers()->orderBy('raffle_numbers.id')->lockForUpdate()->get()->each(function ($number) {
                $number->forceFill(['status' => 'sold', 'reserved_until' => null])->save();
            });

            $lockedOrder->update(['status' => 'approved', 'approved_at' => now()]);

            return true;
        }, 5);

        if (! $processed) {
            return back()->with('status', 'Esta compra ya habia sido procesada anteriormente.');
        }

        $mailSent = $mailService->sendApproved($order->fresh(['raffle', 'numbers']));

        return back()->with('status', $mailSent
            ? 'Pago aprobado. Los numeros quedaron vendidos y el correo quedo programado para enviarse al cliente.'
            : 'Pago aprobado. Los numeros quedaron vendidos, pero no se pudo enviar el correo al cliente. Revisa la configuracion SMTP o el correo del comprador.');
    }

    public function reject(Order $order, OrderMailService $mailService): RedirectResponse
    {
        $processed = DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== 'pending') {
                return false;
            }

            $lockedOrder->numbers()->orderBy('raffle_numbers.id')->lockForUpdate()->get()->each(function ($number) {
                $number->forceFill(['status' => 'available', 'reserved_until' => null])->save();
            });

            $lockedOrder->update(['status' => 'rejected', 'rejected_at' => now()]);

            return true;
        }, 5);

        if (! $processed) {
            return back()->with('status', 'Esta compra ya habia sido procesada anteriormente.');
        }

        $mailSent = $mailService->sendRejected($order->fresh(['raffle', 'numbers']));

        return back()->with('status', $mailSent
            ? 'Pago rechazado. Los numeros volvieron a estar disponibles y el correo quedo programado para enviarse al cliente.'
            : 'Pago rechazado. Los numeros volvieron a estar disponibles, pero no se pudo enviar el correo al cliente. Revisa la configuracion SMTP o el correo del comprador.');
    }
}
