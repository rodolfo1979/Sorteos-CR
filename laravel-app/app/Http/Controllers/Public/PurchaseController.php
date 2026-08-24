<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use App\Services\OrderMailService;
use App\Services\RaffleReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

    public function store(Raffle $raffle, Request $request, RaffleReservationService $service, OrderMailService $mailService): RedirectResponse
    {
        if (! $raffle->sale_enabled) {
            return back()->withErrors(['purchase' => 'La venta de este sorteo esta pausada temporalmente.'])->withInput();
        }
        $validator = Validator::make($request->all(), [
            'buyer_name' => ['required', 'string', 'max:160'],
            'buyer_phone' => ['required', 'string', 'max:40'],
            'buyer_email' => ['required', 'email', 'max:180'],
            'package_count' => ['required', 'integer', 'min:1', 'max:5'],
            'numbers' => ['required', 'array'],
            'numbers.*' => ['required', 'string', 'max:24'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:15360'],
        ], [
            'buyer_email.required' => 'Debes indicar un correo electronico para enviarte tus tickets y el estado del pago.',
            'buyer_email.email' => 'Ingresa un correo electronico valido.',
            'receipt.required' => 'Debes subir la foto o PDF del comprobante de pago.',
            'receipt.file' => 'El comprobante debe ser un archivo valido.',
            'receipt.mimes' => 'El comprobante debe ser una imagen JPG, PNG, WEBP o un PDF.',
            'receipt.max' => 'El comprobante no puede pesar mas de 15 MB. Si es una foto, intenta enviarla como captura o reducir su tamano.',
            'numbers.required' => 'Debes seleccionar al menos un paquete de numeros antes de enviar el comprobante.',
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
                    'email' => $request->string('buyer_email')->trim()->toString(),
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

        $mailService->sendReserved($order);
        $mailService->notifyAdminNewOrder($order);

        return redirect()->route('purchase.confirmation', $order->public_uuid);
    }
}

