<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RaffleController extends Controller
{
    public function edit(Raffle $raffle): View
    {
        return view('admin.raffles.edit', [
            'raffle' => $raffle,
        ]);
    }

    public function update(Request $request, Raffle $raffle): RedirectResponse
    {
        $data = $request->validate([
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
        ]);

        $data['sale_enabled'] = $request->boolean('sale_enabled');

        $raffle->update($data);

        return to_route('admin.raffles.edit', $raffle)->with('status', 'Rifa actualizada correctamente.');
    }
}