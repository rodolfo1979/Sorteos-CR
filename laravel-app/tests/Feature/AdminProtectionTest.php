<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_requires_basic_auth(): void
    {
        $this->seed();

        $this->get('/admin')->assertStatus(401);
    }

    public function test_admin_panel_accepts_configured_basic_auth(): void
    {
        $this->seed();
        config(['admin.username' => 'admin-test', 'admin.password' => 'secure-test-password']);

        $this->withBasicAuth('admin-test', 'secure-test-password')
            ->get('/admin')
            ->assertStatus(200)
            ->assertSee('Dashboard en tiempo real');
    }
}
