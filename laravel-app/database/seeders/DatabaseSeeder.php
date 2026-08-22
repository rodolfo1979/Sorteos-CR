<?php

namespace Database\Seeders;

use App\Models\Raffle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $raffle = Raffle::firstOrCreate(
            ['slug' => 'rifa-moto-2026'],
            [
                'name' => 'Rifa Moto 2026',
                'total_numbers' => 10000,
                'number_width' => 5,
                'price_per_package' => 4000,
                'numbers_per_package' => 2,
                'max_random_changes' => 5,
                'reservation_minutes' => 45,
                'assignment_mode' => 'manual',
                'sale_enabled' => true,
                'is_featured' => true,
                'draw_date' => '2026-09-30',
                'prize_title' => 'Moto nueva y casco',
                'prize_description' => 'Premio principal listo para mostrar con fotografias profesionales.',
                'organizer_name' => 'Rifas CR',
                'organizer_whatsapp' => '8888-8888',
                'payment_instructions' => 'Sube una imagen o captura de tu comprobante para validar tu participacion.',
                'rules_text' => "Validacion: es obligatorio subir la foto del comprobante.\nReserva: los numeros apartados quedan pendientes hasta validar el pago.\nSi el pago es rechazado, los numeros vuelven a estar disponibles.",
            ],
        );

        if ($raffle->numbers()->count() === 0) {
            $batch = [];
            for ($number = 1; $number <= $raffle->total_numbers; $number++) {
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
        }
    }
}
