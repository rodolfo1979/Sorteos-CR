<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_tenants_requires_login_session(): void
    {
        $this->get('/superadmin')
            ->assertRedirect('/superadmin/login');
    }

    public function test_superadmin_login_screen_renders(): void
    {
        $this->get('/superadmin/login')
            ->assertOk()
            ->assertSee('Entrar al super admin')
            ->assertSee('Usuario')
            ->assertSee('Clave');
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

        $this->post('/superadmin/login', [
            'username' => 'admin-test',
            'password' => 'secure-test-password',
        ])->assertSessionHasErrors('username');
    }

    public function test_superadmin_shows_tenant_list_after_login(): void
    {
        $this->loginAsSuperAdmin();

        $this->get('/superadmin')
            ->assertOk()
            ->assertSee('Tenants')
            ->assertSee('Sorteos CR')
            ->assertSee('Crear tenant')
            ->assertSee('Salir');
    }

    public function test_superadmin_logout_clears_session(): void
    {
        $this->loginAsSuperAdmin();

        $this->post('/superadmin/logout')
            ->assertRedirect('/superadmin/login');

        $this->get('/superadmin')->assertRedirect('/superadmin/login');
    }

    public function test_superadmin_can_create_update_and_delete_empty_tenant(): void
    {
        $this->loginAsSuperAdmin();

        $this->post('/superadmin/tenants', [
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
        ])->assertRedirect('/superadmin');

        $tenant = Tenant::query()->where('slug', 'cliente-demo')->firstOrFail();
        $this->assertDatabaseHas('tenant_domains', ['tenant_id' => $tenant->id, 'domain' => 'demo.example.com']);

        $this->put("/superadmin/tenants/{$tenant->id}", [
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
        ])->assertRedirect('/superadmin');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Cliente Demo Editado',
            'status' => 'suspended',
            'notification_email' => 'nuevo@demo.example.com',
        ]);

        $this->delete("/superadmin/tenants/{$tenant->id}")
            ->assertRedirect('/superadmin');

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }

    private function loginAsSuperAdmin(): void
    {
        config([
            'admin.superadmin_username' => 'owner-test',
            'admin.superadmin_password' => 'owner-secure-password',
        ]);

        $this->post('/superadmin/login', [
            'username' => 'owner-test',
            'password' => 'owner-secure-password',
        ])->assertRedirect('/superadmin');
    }
}
