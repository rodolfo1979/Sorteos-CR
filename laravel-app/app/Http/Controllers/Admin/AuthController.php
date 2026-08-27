<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(TenantContext $tenantContext): View
    {
        return view('admin.auth.login', [
            'title' => 'Login admin - Sorteos CR',
            'tenant' => $tenantContext->current(),
        ]);
    }

    public function login(Request $request, TenantContext $tenantContext): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $tenant = $tenantContext->current();

        if (! $this->credentialsAreValid($tenant, $credentials['username'], $credentials['password'])) {
            return back()
                ->withErrors(['username' => 'Credenciales administrativas invalidas.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);
        $request->session()->put('admin_username', $credentials['username']);
        $request->session()->put('admin_tenant_id', $tenant?->id);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_authenticated', 'admin_username', 'admin_tenant_id']);
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Sesion administrativa cerrada.');
    }

    private function credentialsAreValid(?Tenant $tenant, string $username, string $password): bool
    {
        if (filled($tenant?->admin_username) && filled($tenant?->admin_password_hash)) {
            return hash_equals((string) $tenant->admin_username, $username)
                && Hash::check($password, (string) $tenant->admin_password_hash);
        }

        return hash_equals((string) config('admin.username'), $username)
            && hash_equals((string) config('admin.password'), $password);
    }
}
