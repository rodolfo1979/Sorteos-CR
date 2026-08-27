<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('superadmin.auth.login', [
            'title' => 'Login super admin - Sorteos CR',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = (string) config('admin.superadmin_username');
        $password = (string) config('admin.superadmin_password');
        $configured = $username !== '' && $password !== '';

        $validUser = $configured && hash_equals($username, $credentials['username']);
        $validPassword = $configured && hash_equals($password, $credentials['password']);

        if (! $validUser || ! $validPassword) {
            return back()
                ->withErrors(['username' => 'Credenciales de super admin invalidas.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('superadmin_authenticated', true);
        $request->session()->put('superadmin_username', $username);

        return redirect()->intended(route('superadmin.tenants.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['superadmin_authenticated', 'superadmin_username']);
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login')->with('status', 'Sesion super admin cerrada.');
    }
}
