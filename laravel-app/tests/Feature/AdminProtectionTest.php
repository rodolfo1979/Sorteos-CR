<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_requires_login_session(): void
    {
        $this->seed();

        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_login_screen_renders(): void
    {
        $this->seed();

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Iniciar sesion')
            ->assertSee('Usuario')
            ->assertSee('Clave');
    }

    public function test_admin_panel_accepts_configured_login(): void
    {
        $this->seed();
        config(['admin.username' => 'admin-test', 'admin.password' => 'secure-test-password']);

        $this->post('/admin/login', [
            'username' => 'admin-test',
            'password' => 'secure-test-password',
        ])->assertRedirect('/admin');

        $this->get('/admin')
            ->assertStatus(200)
            ->assertSee('Dashboard operativo')
            ->assertSee('Salir');
    }

    public function test_admin_logout_clears_session(): void
    {
        $this->seed();
        config(['admin.username' => 'admin-test', 'admin.password' => 'secure-test-password']);

        $this->post('/admin/login', [
            'username' => 'admin-test',
            'password' => 'secure-test-password',
        ])->assertRedirect('/admin');

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
