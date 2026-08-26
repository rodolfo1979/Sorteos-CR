<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderMailService;
use App\Services\PublicRaffleSnapshotService;
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

    public function show(Order $order): View
    {
        $order->load('raffle', 'numbers');
        $numbers = $order->numbers->pluck('number')->join(', ');
        $statusLabel = match ($order->status) {
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            default => 'Pendiente',
        };
        $whatsappSummary = implode("\n", array_filter([
            'Sorteos CR',
            'Orden: '.strtoupper(substr($order->public_uuid, 0, 8)),
            'Cliente: '.$order->buyer_name,
            'Sorteo: '.($order->raffle?->name ?? 'Sorteo eliminado'),
            'Numeros: '.$numbers,
            'Monto: ₡'.number_format($order->amount_total, 0, ',', ' '),
            'Estado: '.$statusLabel,
        ]));

        return view('admin.payments.show', [
            'order' => $order,
            'numbers' => $numbers,
            'statusLabel' => $statusLabel,
            'whatsappSummary' => $whatsappSummary,
        ]);
    }

    public function resendEmail(Order $order, Request $request, OrderMailService $mailService): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:reserved,approved,rejected,admin'],
        ]);

        $order->load('raffle', 'numbers');

        $sent = match ($validated['type']) {
            'reserved' => $mailService->sendReserved($order),
            'approved' => $mailService->sendApproved($order),
            'rejected' => $mailService->sendRejected($order),
            'admin' => tap(true, fn () => $mailService->notifyAdminNewOrder($order)),
        };

        return back()->with('status', $sent
            ? 'Correo reenviado correctamente.'
            : 'No se pudo reenviar el correo. Revisa el correo del comprador o la configuracion SMTP.');
    }

    public function approve(Order $order, OrderMailService $mailService, PublicRaffleSnapshotService $snapshotService): RedirectResponse
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

        $freshOrder = $order->fresh(['raffle', 'numbers']);
        $snapshotService->adjustCounts($freshOrder->raffle, reservedDelta: -$freshOrder->numbers->count(), soldDelta: $freshOrder->numbers->count());
        $mailSent = $mailService->sendApproved($freshOrder);

        return back()->with('status', $mailSent
            ? 'Pago aprobado. Los numeros quedaron vendidos y el correo fue enviado al cliente.'
            : 'Pago aprobado. Los numeros quedaron vendidos, pero no se pudo enviar el correo al cliente. Revisa la configuracion SMTP o el correo del comprador.');
    }

    public function reject(Order $order, OrderMailService $mailService, PublicRaffleSnapshotService $snapshotService): RedirectResponse
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

        $freshOrder = $order->fresh(['raffle', 'numbers']);
        $snapshotService->adjustCounts($freshOrder->raffle, availableDelta: $freshOrder->numbers->count(), reservedDelta: -$freshOrder->numbers->count());
        $mailSent = $mailService->sendRejected($freshOrder);

        return back()->with('status', $mailSent
            ? 'Pago rechazado. Los numeros volvieron a estar disponibles y el correo fue enviado al cliente.'
            : 'Pago rechazado. Los numeros volvieron a estar disponibles, pero no se pudo enviar el correo al cliente. Revisa la configuracion SMTP o el correo del comprador.');
    }
}
