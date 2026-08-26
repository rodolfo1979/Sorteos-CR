<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'errors' => $this->recentLogLines(),
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

    private function safeCount(string $table): int|string
    {
        try {
            return DB::table($table)->count();
        } catch (Throwable $exception) {
            return 'No disponible';
        }
    }

    private function recentLogLines(): array
    {
        $path = storage_path('logs/laravel.log');

        if (! File::exists($path)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', File::get($path)) ?: [];
        $matches = array_values(array_filter($lines, fn (string $line) => str_contains($line, 'production.ERROR')
            || str_contains($line, 'production.WARNING')
            || str_contains($line, 'Correo')
            || str_contains($line, 'Laravel completo envio')));

        return array_slice($matches, -30);
    }
}
