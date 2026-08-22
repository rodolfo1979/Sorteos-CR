<?php

use App\Models\Raffle;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('raffles:rebuild-numbers {raffle_id} {--force}', function (int $raffleId): int {
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
