<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use App\Services\PublicRaffleSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RaffleController extends Controller
{
    public function show(?string $slug = null): View
    {
        $snapshotService = app(PublicRaffleSnapshotService::class);
        $raffle = $slug ? $snapshotService->bySlug($slug) : $snapshotService->featured();

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
