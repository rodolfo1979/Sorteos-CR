<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RaffleController extends Controller
{
    public function create(): View
    {
        return view('admin.raffles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request, true);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['sale_enabled'] = $request->boolean('sale_enabled');
        $data['is_featured'] = $request->boolean('is_featured');

        if ($data['is_featured']) {
            Raffle::query()->update(['is_featured' => false]);
        }

        $raffle = Raffle::create($data);
        $this->createNumbers($raffle);

        return to_route('admin.raffles.edit', $raffle)->with('status', 'Sorteo creado correctamente.');
    }

    public function edit(Raffle $raffle): View
    {
        return view('admin.raffles.edit', [
            'raffle' => $raffle,
        ]);
    }

    public function update(Request $request, Raffle $raffle): RedirectResponse
    {
        $data = $this->validatedData($request, false);
        $data['sale_enabled'] = $request->boolean('sale_enabled');
        $data['is_featured'] = $request->boolean('is_featured');

        if ($data['is_featured']) {
            Raffle::whereKeyNot($raffle->id)->update(['is_featured' => false]);
        }

        $raffle->update($data);

        return to_route('admin.raffles.edit', $raffle)->with('status', 'Rifa actualizada correctamente.');
    }

    private function validatedData(Request $request, bool $creating): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:160'],
            'prize_title' => ['nullable', 'string', 'max:180'],
            'prize_description' => ['nullable', 'string', 'max:5000'],
            'public_sales_text' => ['nullable', 'string', 'max:12000'],
            'rules_text' => ['nullable', 'string', 'max:12000'],
            'payment_instructions' => ['nullable', 'string', 'max:8000'],
            'organizer_name' => ['nullable', 'string', 'max:160'],
            'organizer_whatsapp' => ['nullable', 'string', 'max:40'],
            'draw_date' => ['nullable', 'date'],
            'price_per_package' => ['required', 'integer', 'min:1'],
            'numbers_per_package' => ['required', 'integer', 'min:1', 'max:100'],
            'max_random_changes' => ['required', 'integer', 'min:0', 'max:50'],
            'reservation_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'assignment_mode' => ['required', 'in:manual,random'],
        ];

        if ($creating) {
            $rules['total_numbers'] = ['required', 'integer', 'min:1', 'max:100000'];
            $rules['number_width'] = ['required', 'integer', 'min:2', 'max:6'];
        }

        return $request->validate($rules);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'sorteo';
        $slug = $base;
        $suffix = 2;

        while (Raffle::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function createNumbers(Raffle $raffle): void
    {
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
    }
}
