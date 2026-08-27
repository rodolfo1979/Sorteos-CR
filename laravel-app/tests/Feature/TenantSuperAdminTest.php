<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_tenants_requires_its_own_basic_auth(): void
    {
        $this->get('/superadmin/tenants')->assertStatus(401);
    }

    public function test_superadmin_tenants_is_not_available_inside_admin_routes(): void
    {
        config(['admin.username' => 'admin-test', 'admin.password' => 'secure-test-password']);

        $this->withBasicAuth('admin-test', 'secure-test-password')
            ->get('/admin/plataforma')
            ->assertNotFound();
    }

    public function test_superadmin_rejects_regular_admin_credentials(): void
    {
        config([
            'admin.username' => 'admin-test',
            'admin.password' => 'secure-test-password',
            'admin.superadmin_username' => 'owner-test',
            'admin.superadmin_password' => 'owner-secure-password',
        ]);

        $this->withBasicAuth('admin-test', 'secure-test-password')
            ->get('/superadmin/tenants')
            ->assertStatus(401);
    }

    public function test_superadmin_shows_tenant_list_with_superadmin_credentials(): void
    {
        $this->actingAsSuperAdmin()
            ->get('/superadmin/tenants')
            ->assertOk()
            ->assertSee('Tenants')
            ->assertSee('Sorteos CR')
            ->assertSee('Crear tenant');
    }

    public function test_superadmin_can_create_update_and_delete_empty_tenant(): void
    {
        $this->actingAsSuperAdmin()
            ->post('/superadmin/tenants', [
                'name' => 'Cliente Demo',
                'slug' => 'cliente-demo',
                'status' => 'active',
                'primary_domain' => 'demo.example.com',
                'admin_email' => 'admin@demo.example.com',
                'notification_email' => 'avisos@demo.example.com',
                'timezone' => 'America/Costa_Rica',
                'currency' => 'CRC',
                'primary_color' => '#0f172a',
                'accent_color' => '#0891b2',
            ])
            ->assertRedirect('/superadmin/tenants');

        $tenant = Tenant::query()->where('slug', 'cliente-demo')->firstOrFail();
        $this->assertDatabaseHas('tenant_domains', ['tenant_id' => $tenant->id, 'domain' => 'demo.example.com']);

        $this->actingAsSuperAdmin()
            ->put("/superadmin/tenants/{$tenant->id}", [
                'name' => 'Cliente Demo Editado',
                'slug' => 'cliente-demo',
                'status' => 'suspended',
                'primary_domain' => 'demo.example.com',
                'admin_email' => 'admin@demo.example.com',
                'notification_email' => 'nuevo@demo.example.com',
                'timezone' => 'America/Costa_Rica',
                'currency' => 'CRC',
                'primary_color' => '#111827',
                'accent_color' => '#0e7490',
            ])
            ->assertRedirect('/superadmin/tenants');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Cliente Demo Editado',
            'status' => 'suspended',
            'notification_email' => 'nuevo@demo.example.com',
        ]);

        $this->actingAsSuperAdmin()
            ->delete("/superadmin/tenants/{$tenant->id}")
            ->assertRedirect('/superadmin/tenants');

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }

    private function actingAsSuperAdmin(): self
    {
        config([
            'admin.superadmin_username' => 'owner-test',
            'admin.superadmin_password' => 'owner-secure-password',
        ]);

        return $this->withBasicAuth('owner-test', 'owner-secure-password');
    }
}
