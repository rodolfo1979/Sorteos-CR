<?php

use App\Models\Raffle;
use App\Services\PublicRaffleSnapshotService;
use App\Services\RaffleReservationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('raffles:rebuild-numbers {raffle_id} {--force}', function (): int {
    $raffleId = (int) $this->argument('raffle_id');
    $raffle = Raffle::find($raffleId);

    if (! $raffle) {
        $this->error('No se encontro la rifa indicada.');

        return 1;
    }

    $ordersCount = $raffle->orders()->count();
    if ($ordersCount > 0 && ! $this->option('force')) {
        $this->error("Esta rifa tiene {$ordersCount} compra(s). Usa --force solo si son pruebas y deseas borrarlas.");

        return 1;
    }

    DB::transaction(function () use ($raffle) {
        $raffle->orders()->delete();
        $raffle->numbers()->delete();
        $raffle->forceFill([
            'number_width' => max(1, strlen((string) max(0, $raffle->total_numbers - 1))),
        ])->save();

        $batch = [];
        for ($number = $raffle->numberStart(); $number <= $raffle->numberEnd(); $number++) {
            $batch[] = [
                'raffle_id' => $raffle->id,
                'number' => $raffle->formatNumber($number),
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) === 1000) {
                $raffle->numbers()->insert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $raffle->numbers()->insert($batch);
        }
    });

    $this->info("Rifa {$raffle->id} reconstruida: {$raffle->formatNumber($raffle->numberStart())} hasta {$raffle->formatNumber($raffle->numberEnd())}.");

    return 0;
})->purpose('Reconstruye los numeros de una rifa desde cero. Borra compras si se usa --force.');

Artisan::command('mail:test {email}', function (): int {
    $email = (string) $this->argument('email');

    try {
        Mail::raw('Correo de prueba enviado correctamente desde Sorteos CR.', function ($message) use ($email) {
            $message->to($email)->subject('Prueba de correo - Sorteos CR');
        });
    } catch (Throwable $exception) {
        $this->error('No se pudo enviar el correo: '.$exception->getMessage());

        return 1;
    }

    $this->info('Correo de prueba enviado a '.$email.'.');

    return 0;
})->purpose('Envia un correo de prueba para validar la configuracion SMTP.');

Artisan::command('raffles:release-expired-reservations', function (RaffleReservationService $reservationService): int {
    $released = 0;

    Raffle::query()->chunkById(50, function ($raffles) use (&$released, $reservationService) {
        foreach ($raffles as $raffle) {
            $released += $reservationService->releaseExpiredReservations($raffle);
        }
    });

    $this->info("Reservas vencidas liberadas: {$released}.");

    return 0;
})->purpose('Libera numeros reservados cuyo tiempo de reserva ya vencio.');
Artisan::command('raffles:warm-public-snapshot', function (PublicRaffleSnapshotService $snapshotService): int {
    $raffle = $snapshotService->warmFeatured();

    $this->info("Snapshot publico actualizado para: {$raffle->name}.");

    return 0;
})->purpose('Precalienta el snapshot de la pagina publica de venta.');
