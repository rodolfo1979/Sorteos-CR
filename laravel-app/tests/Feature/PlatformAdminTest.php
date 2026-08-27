<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_panel_requires_its_own_basic_auth(): void
    {
        $this->get('/superadmin')->assertStatus(401);
    }

    public function test_superadmin_panel_is_not_available_inside_admin_routes(): void
    {
        config(['admin.username' => 'admin-test', 'admin.password' => 'secure-test-password']);

        $this->withBasicAuth('admin-test', 'secure-test-password')
            ->get('/admin/plataforma')
            ->assertNotFound();
    }

    public function test_superadmin_panel_rejects_regular_admin_credentials(): void
    {
        config([
            'admin.username' => 'admin-test',
            'admin.password' => 'secure-test-password',
            'admin.superadmin_username' => 'owner-test',
            'admin.superadmin_password' => 'owner-secure-password',
        ]);

        $this->withBasicAuth('admin-test', 'secure-test-password')
            ->get('/superadmin')
            ->assertStatus(401);
    }

    public function test_superadmin_panel_shows_tenant_summary_with_superadmin_credentials(): void
    {
        config([
            'admin.superadmin_username' => 'owner-test',
            'admin.superadmin_password' => 'owner-secure-password',
        ]);

        $this->withBasicAuth('owner-test', 'owner-secure-password')
            ->get('/superadmin')
            ->assertOk()
            ->assertSee('Plataforma')
            ->assertSee('Sorteos CR')
            ->assertSee('sorteos-cr')
            ->assertSee('Solo lectura');
    }
}
