<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use App\Services\RaffleReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;
use RuntimeException;

class PurchaseController extends Controller
{
    public function random(Raffle $raffle, Request $request, RaffleReservationService $service): JsonResponse
    {
        abort_unless($raffle->sale_enabled, 423, 'La venta de este sorteo esta pausada temporalmente.');
        $validated = $request->validate([
            'package_count' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $numbers = $service->randomNumbers($raffle, (int) $validated['package_count']);

        return response()->json([
            'numbers' => $numbers,
            'quantity' => count($numbers),
        ]);
    }

    public function store(Raffle $raffle, Request $request, RaffleReservationService $service): RedirectResponse
    {
        if (! $raffle->sale_enabled) {
            return back()->withErrors(['purchase' => 'La venta de este sorteo esta pausada temporalmente.'])->withInput();
        }
        $validator = Validator::make($request->all(), [
            'buyer_name' => ['required', 'string', 'max:160'],
            'buyer_phone' => ['required', 'string', 'max:40'],
            'buyer_email' => ['nullable', 'email', 'max:180'],
            'package_count' => ['required', 'integer', 'min:1', 'max:5'],
            'numbers' => ['required', 'array'],
            'numbers.*' => ['required', 'string', 'max:24'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $receiptFile = $request->file('receipt');
        $receiptPath = $receiptFile->store('receipts', 'public');

        try {
            $order = $service->reserve(
                $raffle,
                [
                    'name' => $request->string('buyer_name')->trim()->toString(),
                    'phone' => $request->string('buyer_phone')->trim()->toString(),
                    'email' => $request->string('buyer_email')->trim()->toString() ?: null,
                ],
                $request->input('numbers', []),
                [
                    'path' => $receiptPath,
                    'original_name' => $receiptFile->getClientOriginalName(),
                    'mime' => $receiptFile->getMimeType(),
                ],
                (int) $request->integer('package_count'),
                (int) $request->integer('random_changes_used', 0),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['purchase' => $exception->getMessage()])->withInput();
        }

        if ($order->buyer_email) {
            try {
                $order->load('raffle', 'numbers');
                Mail::send('emails.order-reserved', ['order' => $order], function ($message) use ($order) {
                    $message->to($order->buyer_email, $order->buyer_name)
                        ->subject('Tus tickets fueron reservados - '.$order->raffle->name);
                });
            } catch (Throwable $exception) {
                Log::warning('No se pudo enviar correo de reserva.', ['order_id' => $order->id, 'error' => $exception->getMessage()]);
            }
        }

        return redirect()->route('purchase.confirmation', $order->public_uuid);
    }
}



