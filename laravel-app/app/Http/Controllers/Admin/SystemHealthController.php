<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Throwable;

class SystemHealthController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.health.index', [
            'queue' => $this->queueSnapshot(),
            'mail' => $this->mailSnapshot(),
            'schedule' => $this->scheduleSnapshot(),
            'recentOrders' => $this->recentOrders(),
            'mailEvents' => $this->recentMailLines(),
            'errors' => $this->recentErrorLines(),
        ]);
    }

    private function queueSnapshot(): array
    {
        return [
            'pending_jobs' => $this->safeCount('jobs'),
            'failed_jobs' => $this->safeCount('failed_jobs'),
            'connection' => config('queue.default'),
        ];
    }

    private function mailSnapshot(): array
    {
        return [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username') ? 'configurado' : 'vacio',
            'password' => config('mail.mailers.smtp.password') ? 'configurado' : 'vacio',
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];
    }

    private function scheduleSnapshot(): array
    {
        try {
            Artisan::call('schedule:list');

            return [
                'ok' => true,
                'output' => trim(Artisan::output()),
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'output' => $exception->getMessage(),
            ];
        }
    }

    private function recentOrders(): array
    {
        try {
            return Order::query()
                ->with(['raffle', 'numbers'])
                ->latest()
                ->limit(8)
                ->get()
                ->map(function (Order $order) {
                    $numbers = $order->numbers
                        ->pluck('number')
                        ->filter(fn ($number) => $number !== null)
                        ->map(fn ($number) => str_pad((string) $number, $order->raffle?->effectiveNumberWidth() ?? 5, '0', STR_PAD_LEFT))
                        ->values()
                        ->all();
                    $statusLabel = match ($order->status) {
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        default => 'Pendiente',
                    };
                    $emailType = match ($order->status) {
                        'approved' => 'approved',
                        'rejected' => 'rejected',
                        default => 'reserved',
                    };
                    $emailLabel = match ($emailType) {
                        'approved' => 'Reenviar aprobacion',
                        'rejected' => 'Reenviar rechazo',
                        default => 'Reenviar reserva',
                    };
                    $summary = implode("\n", array_filter([
                        'Sorteos CR',
                        'Orden: '.strtoupper(substr($order->public_uuid, 0, 8)),
                        'Cliente: '.$order->buyer_name,
                        'Sorteo: '.($order->raffle?->name ?? 'Sorteo eliminado'),
                        'Numeros: '.implode(', ', $numbers),
                        'Monto: ₡'.number_format($order->amount_total, 0, ',', ' '),
                        'Estado: '.$statusLabel,
                    ]));
                    $whatsappPhone = preg_replace('/\D+/', '', $order->buyer_phone ?? '');

                    return [
                        'id' => $order->id,
                        'buyer_name' => $order->buyer_name,
                        'buyer_email' => $order->buyer_email,
                        'buyer_phone' => $order->buyer_phone,
                        'raffle' => $order->raffle?->name ?? 'Sin sorteo',
                        'status' => $order->status,
                        'status_label' => $statusLabel,
                        'amount_total' => $order->amount_total,
                        'numbers' => $numbers,
                        'created_at' => $order->created_at?->timezone('America/Costa_Rica')->format('d/m/Y H:i:s'),
                        'updated_at' => $order->updated_at?->timezone('America/Costa_Rica')->format('d/m/Y H:i:s'),
                        'detail_url' => route('admin.payments.show', $order),
                        'resend_url' => route('admin.payments.resend-email', $order),
                        'email_type' => $emailType,
                        'email_label' => $emailLabel,
                        'whatsapp_url' => $whatsappPhone !== '' ? 'https://wa.me/'.$whatsappPhone.'?text='.rawurlencode($summary) : null,
                    ];
                })
                ->all();
        } catch (Throwable $exception) {
            return [];
        }
    }
    private function safeCount(string $table): int|string
    {
        try {
            return DB::table($table)->count();
        } catch (Throwable $exception) {
            return 'No disponible';
        }
    }

    private function recentMailLines(): array
    {
        return array_slice(array_values(array_filter($this->logLines(), fn (string $line) => str_contains($line, 'Correo')
            || str_contains($line, 'Laravel iniciando envio')
            || str_contains($line, 'Laravel completo envio'))), -20);
    }

    private function recentErrorLines(): array
    {
        return array_slice(array_values(array_filter($this->logLines(), fn (string $line) => str_contains($line, 'production.ERROR'))), -20);
    }

    private function logLines(): array
    {
        $path = storage_path('logs/laravel.log');

        if (! File::exists($path)) {
            return [];
        }

        return preg_split('/\r\n|\r|\n/', File::get($path)) ?: [];
    }
}