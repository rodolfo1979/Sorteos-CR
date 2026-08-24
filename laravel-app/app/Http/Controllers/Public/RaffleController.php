<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class RaffleController extends Controller
{
    public function show(?string $slug = null): View
    {
        $raffle = Cache::remember($slug ? "raffle:public:slug:{$slug}" : 'raffle:public:featured', now()->addSeconds(5), function () use ($slug) {
            return $slug
                ? Raffle::where('slug', $slug)->firstOrFail()
                : Raffle::where('is_featured', true)->first() ?? Raffle::latest()->firstOrFail();
        });

        $counts = Cache::remember("raffle:{$raffle->id}:public-counts", now()->addSeconds(5), function () use ($raffle) {
            $statusCounts = $raffle->numbers()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'available_count' => (int) ($statusCounts['available'] ?? 0),
                'sold_count' => (int) ($statusCounts['sold'] ?? 0),
                'reserved_count' => (int) ($statusCounts['reserved'] ?? 0),
            ];
        });

        foreach ($counts as $key => $value) {
            $raffle->setAttribute($key, $value);
        }

        return view('raffles.show', [
            'raffle' => $raffle,
            'packageOptions' => $raffle->packageOptions(),
        ]);
    }

    public function numbers(Raffle $raffle, Request $request): JsonResponse
    {
        $perPage = min(1000, max(50, (int) $request->integer('per_page', 100)));
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
