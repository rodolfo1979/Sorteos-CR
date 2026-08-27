<?php

namespace App\Services;

use App\Models\Raffle;
use Illuminate\Support\Facades\Cache;

class PublicRaffleSnapshotService
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function featured(): Raffle
    {
        return $this->remember('featured', function () {
            return Raffle::query()
                ->when($this->currentTenantId(), fn ($query, int $tenantId) => $query->where(function ($inner) use ($tenantId) {
                    $inner->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                }))
                ->where('is_featured', true)
                ->first()
                ?? Raffle::query()
                    ->when($this->currentTenantId(), fn ($query, int $tenantId) => $query->where(function ($inner) use ($tenantId) {
                        $inner->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                    }))
                    ->latest()
                    ->firstOrFail();
        });
    }

    public function bySlug(string $slug): Raffle
    {
        return $this->remember("slug:{$slug}", fn () => Raffle::query()
            ->when($this->currentTenantId(), fn ($query, int $tenantId) => $query->where(function ($inner) use ($tenantId) {
                $inner->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            }))
            ->where('slug', $slug)
            ->firstOrFail());
    }

    public function byId(int $id): Raffle
    {
        return $this->remember("id:{$id}", fn () => Raffle::query()
            ->when($this->currentTenantId(), fn ($query, int $tenantId) => $query->where(function ($inner) use ($tenantId) {
                $inner->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            }))
            ->whereKey($id)
            ->firstOrFail());
    }

    public function warmFeatured(): Raffle
    {
        $raffle = Raffle::query()
            ->when($this->currentTenantId(), fn ($query, int $tenantId) => $query->where(function ($inner) use ($tenantId) {
                $inner->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            }))
            ->where('is_featured', true)
            ->first()
            ?? Raffle::query()
                ->when($this->currentTenantId(), fn ($query, int $tenantId) => $query->where(function ($inner) use ($tenantId) {
                    $inner->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                }))
                ->latest()
                ->firstOrFail();
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
        Cache::forget($this->cacheKey('featured'));
        Cache::forget('public-raffle:snapshot:featured');

        if ($raffle) {
            foreach ($this->tenantIdsFor($raffle) as $tenantId) {
                Cache::forget($this->cacheKey("id:{$raffle->id}", $tenantId));
                Cache::forget($this->cacheKey("slug:{$raffle->slug}", $tenantId));
            }

            Cache::forget("public-raffle:snapshot:id:{$raffle->id}");
            Cache::forget("public-raffle:snapshot:slug:{$raffle->slug}");
        }
    }

    public function adjustCounts(Raffle $raffle, int $availableDelta = 0, int $reservedDelta = 0, int $soldDelta = 0): void
    {
        foreach (['featured', "id:{$raffle->id}", "slug:{$raffle->slug}"] as $key) {
            foreach ($this->tenantIdsFor($raffle) as $tenantId) {
                $this->adjustSnapshot($key, $raffle, $availableDelta, $reservedDelta, $soldDelta, $tenantId);
            }

            $this->adjustSnapshot($key, $raffle, $availableDelta, $reservedDelta, $soldDelta, null, false);
        }
    }

    private function adjustSnapshot(string $key, Raffle $raffle, int $availableDelta, int $reservedDelta, int $soldDelta, ?int $tenantId = null, bool $scoped = true): void
    {
        $cacheKey = $scoped ? $this->cacheKey($key, $tenantId) : "public-raffle:snapshot:{$key}";
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
        $cacheKey = $this->cacheKey($key);
        $snapshot = Cache::get($cacheKey);

        if (! is_array($snapshot)) {
            $legacySnapshot = Cache::get("public-raffle:snapshot:{$key}");

            if (is_array($legacySnapshot) && $this->snapshotBelongsToCurrentTenant($legacySnapshot)) {
                $snapshot = $legacySnapshot;
                Cache::forever($cacheKey, $snapshot);
            } else {
                $raffle = $resolver();
                $snapshot = $this->snapshot($raffle);
                Cache::forever($cacheKey, $snapshot);
            }
        }

        return $this->hydrate($snapshot);
    }

    private function store(string $key, Raffle $raffle): void
    {
        Cache::forever($this->cacheKey($key, $raffle->tenant_id), $this->snapshot($raffle));
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

    private function cacheKey(string $key, ?int $tenantId = null): string
    {
        return 'public-raffle:snapshot:tenant:'.($tenantId ?: $this->currentTenantId() ?: 'legacy').":{$key}";
    }

    private function currentTenantId(): ?int
    {
        return $this->tenantContext->current()?->id;
    }

    private function tenantIdsFor(Raffle $raffle): array
    {
        return array_values(array_unique(array_filter([
            $raffle->tenant_id ? (int) $raffle->tenant_id : null,
            $this->currentTenantId(),
        ])));
    }

    private function snapshotBelongsToCurrentTenant(array $snapshot): bool
    {
        $snapshotTenantId = $snapshot['attributes']['tenant_id'] ?? null;
        $currentTenantId = $this->currentTenantId();

        return ! $currentTenantId || ! $snapshotTenantId || (int) $snapshotTenantId === $currentTenantId;
    }
}