<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use Illuminate\View\View;

class RaffleController extends Controller
{
    public function show(?string $slug = null): View
    {
        $raffle = $slug
            ? Raffle::where('slug', $slug)->firstOrFail()
            : Raffle::where('is_featured', true)->first() ?? Raffle::latest()->firstOrFail();

        $raffle->loadCount([
            'numbers as available_count' => fn ($query) => $query->where('status', 'available'),
            'numbers as sold_count' => fn ($query) => $query->where('status', 'sold'),
            'numbers as reserved_count' => fn ($query) => $query->where('status', 'reserved'),
        ]);

        return view('raffles.show', [
            'raffle' => $raffle,
            'packageOptions' => $raffle->packageOptions(),
        ]);
    }
}
