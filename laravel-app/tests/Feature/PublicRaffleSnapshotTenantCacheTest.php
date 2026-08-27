<?php

namespace Tests\Feature;

use App\Models\Raffle;
use App\Models\RaffleNumber;
use App\Models\Tenant;
use App\Services\PublicRaffleSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicRaffleSnapshotTenantCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_snapshot_is_stored_with_tenant_scoped_key(): void
    {
        Cache::flush();

        $tenant = Tenant::query()->where('slug', 'sorteos-cr')->firstOrFail();
        $raffle = Raffle::create([
            'tenant_id' => $tenant->id,
            'name' => 'Rifa tenant cache',
            'slug' => 'rifa-tenant-cache',
            'total_numbers' => 10,
            'number_width' => 2,
            'price_per_package' => 1000,
            'numbers_per_package' => 1,
            'max_random_changes' => 5,
            'reservation_minutes' => 45,
            'assignment_mode' => 'manual',
            'sale_enabled' => true,
            'is_featured' => true,
            'organizer_name' => 'Sorteos CR',
        ]);

        RaffleNumber::create([
            'tenant_id' => $tenant->id,
            'raffle_id' => $raffle->id,
            'number' => '01',
            'status' => 'available',
        ]);

        app(PublicRaffleSnapshotService::class)->warm($raffle);

        $this->assertTrue(Cache::has("public-raffle:snapshot:tenant:{$tenant->id}:id:{$raffle->id}"));
        $this->assertFalse(Cache::has("public-raffle:snapshot:id:{$raffle->id}"));
    }
}