<?php

namespace App\Services;

use App\Models\Raffle;
use Illuminate\Support\Facades\Cache;

class PublicRaffleSnapshotService
{
    public function featured(): Raffle
    {
        return $this->remember('featured', function () {
            return Raffle::where('is_featured', true)->first() ?? Raffle::latest()->firstOrFail();
        });
    }

    public function bySlug(string $slug): Raffle
    {
        return $this->remember("slug:{$slug}", fn () => Raffle::where('slug', $slug)->firstOrFail());
    }

    public function byId(int $id): Raffle
    {
        return $this->remember("id:{$id}", fn () => Raffle::findOrFail($id));
    }

    public function warmFeatured(): Raffle
    {
        $raffle = Raffle::where('is_featured', true)->first() ?? Raffle::latest()->firstOrFail();
        $this->store('featured', $raffle);
        $this->store("id:{$raffle->id}", $raffle);
        $this->store("slug:{$raffle->slug}", $raffle);

        return $raffle;
    }

    public function warm(Raffle $raffle): void
    {
        $fresh = $raffle->fresh() ?? $raffle;
        $this->store("id:{$fresh->id}", $fresh);
        $this->store("slug:{$fresh->slug}", $fresh);

        if ($fresh->is_featured) {
            $this->store('featured', $fresh);
        }
    }

    public function forget(?Raffle $raffle = null): void
    {
        Cache::forget('public-raffle:snapshot:featured');

        if ($raffle) {
            Cache::forget("public-raffle:snapshot:id:{$raffle->id}");
            Cache::forget("public-raffle:snapshot:slug:{$raffle->slug}");
        }
    }

    public function adjustCounts(Raffle $raffle, int $availableDelta = 0, int $reservedDelta = 0, int $soldDelta = 0): void
    {
        foreach (['featured', "id:{$raffle->id}", "slug:{$raffle->slug}"] as $key) {
            $this->adjustSnapshot($key, $raffle, $availableDelta, $reservedDelta, $soldDelta);
        }
    }

    private function adjustSnapshot(string $key, Raffle $raffle, int $availableDelta, int $reservedDelta, int $soldDelta): void
    {
        $cacheKey = "public-raffle:snapshot:{$key}";
        $snapshot = Cache::get($cacheKey);

        if (! is_array($snapshot) || (int) ($snapshot['attributes']['id'] ?? 0) !== (int) $raffle->id) {
            return;
        }

        $snapshot['counts']['available_count'] = max(0, (int) ($snapshot['counts']['available_count'] ?? 0) + $availableDelta);
        $snapshot['counts']['reserved_count'] = max(0, (int) ($snapshot['counts']['reserved_count'] ?? 0) + $reservedDelta);
        $snapshot['counts']['sold_count'] = max(0, (int) ($snapshot['counts']['sold_count'] ?? 0) + $soldDelta);

        Cache::forever($cacheKey, $snapshot);
    }

    private function remember(string $key, callable $resolver): Raffle
    {
        $cacheKey = "public-raffle:snapshot:{$key}";
        $snapshot = Cache::get($cacheKey);

        if (! is_array($snapshot)) {
            $raffle = $resolver();
            $snapshot = $this->snapshot($raffle);
            Cache::forever($cacheKey, $snapshot);
        }

        return $this->hydrate($snapshot);
    }

    private function store(string $key, Raffle $raffle): void
    {
        Cache::forever("public-raffle:snapshot:{$key}", $this->snapshot($raffle));
    }

    private function snapshot(Raffle $raffle): array
    {
        $statusCounts = $raffle->numbers()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'attributes' => $raffle->getAttributes(),
            'counts' => [
                'available_count' => (int) ($statusCounts['available'] ?? 0),
                'sold_count' => (int) ($statusCounts['sold'] ?? 0),
                'reserved_count' => (int) ($statusCounts['reserved'] ?? 0),
            ],
        ];
    }

    private function hydrate(array $snapshot): Raffle
    {
        $raffle = new Raffle();
        $raffle->setRawAttributes($snapshot['attributes'] ?? [], true);
        $raffle->exists = true;

        foreach (($snapshot['counts'] ?? []) as $key => $value) {
            $raffle->setAttribute($key, $value);
        }

        return $raffle;
    }
}
