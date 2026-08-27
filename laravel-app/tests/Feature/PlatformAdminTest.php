<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_panel_requires_basic_auth(): void
    {
        $this->get('/admin/plataforma')->assertStatus(401);
    }

    public function test_platform_panel_shows_tenant_summary(): void
    {
        config(['admin.username' => 'admin-test', 'admin.password' => 'secure-test-password']);

        $this->withBasicAuth('admin-test', 'secure-test-password')
            ->get('/admin/plataforma')
            ->assertOk()
            ->assertSee('Plataforma')
            ->assertSee('Sorteos CR')
            ->assertSee('sorteos-cr')
            ->assertSee('Solo lectura');
    }
}
