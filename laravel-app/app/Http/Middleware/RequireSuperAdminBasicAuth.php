<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSuperAdminBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = (string) config('admin.superadmin_username');
        $password = (string) config('admin.superadmin_password');
        $configured = $username !== '' && $password !== '';

        $validUser = $configured && hash_equals($username, (string) $request->getUser());
        $validPassword = $configured && hash_equals($password, (string) $request->getPassword());

        if (! $validUser || ! $validPassword) {
            return response('Acceso super admin protegido.', 401, [
                'WWW-Authenticate' => 'Basic realm="Sorteos CR Super Admin"',
            ]);
        }

        return $next($request);
    }
}
