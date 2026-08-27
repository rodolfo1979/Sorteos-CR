<?php

namespace Tests\Feature;

use App\Models\Raffle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantDiagnoseTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_diagnose_passes_when_foundation_is_clean(): void
    {
        $this->artisan('tenants:diagnose')
            ->expectsOutput('TENANT=Sorteos CR')
            ->expectsOutput('UNASSIGNED_RAFFLES=0')
            ->expectsOutput('UNASSIGNED_RAFFLE_NUMBERS=0')
            ->expectsOutput('UNASSIGNED_ORDERS=0')
            ->expectsOutput('UNASSIGNED_ORDER_EVENTS=0')
            ->expectsOutput('Integridad multitenant correcta.')
            ->assertSuccessful();
    }

    public function test_tenant_diagnose_fails_when_any_record_is_unassigned(): void
    {
        Raffle::query()->create([
            'tenant_id' => null,
            'name' => 'Rifa sin tenant',
            'slug' => 'rifa-sin-tenant',
            'total_numbers' => 100,
            'number_width' => 3,
            'price_per_package' => 1000,
            'numbers_per_package' => 1,
            'max_random_changes' => 5,
            'reservation_minutes' => 45,
            'assignment_mode' => 'manual',
            'sale_enabled' => true,
            'is_featured' => false,
            'organizer_name' => 'Sorteos CR',
        ]);

        $this->artisan('tenants:diagnose')
            ->expectsOutput('UNASSIGNED_RAFFLES=1')
            ->expectsOutput('Integridad multitenant requiere revision.')
            ->assertFailed();
    }
}