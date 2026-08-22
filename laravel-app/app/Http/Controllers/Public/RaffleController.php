<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function numbers(Raffle $raffle, Request $request): JsonResponse
    {
        $perPage = min(1000, max(100, (int) $request->integer('per_page', 1000)));
        $totalPages = max(1, (int) ceil($raffle->total_numbers / $perPage));
        $page = min($totalPages, max(1, (int) $request->integer('page', 1)));
        $start = ($page - 1) * $perPage;
        $end = min($raffle->numberEnd(), $start + $perPage - 1);

        $numbers = $raffle->numbers()
            ->whereBetween('number', [$raffle->formatNumber($start), $raffle->formatNumber($end)])
            ->orderBy('number')
            ->get(['number', 'status'])
            ->map(fn ($number) => [
                'number' => $number->number,
                'available' => $number->status === 'available',
            ])
            ->values();

        return response()->json([
            'numbers' => $numbers,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'range' => $raffle->formatNumber($start).' - '.$raffle->formatNumber($end),
        ]);
    }
}