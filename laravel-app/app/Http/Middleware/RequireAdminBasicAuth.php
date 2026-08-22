<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = (string) config('admin.username');
        $password = (string) config('admin.password');

        $validUser = hash_equals($username, (string) $request->getUser());
        $validPassword = hash_equals($password, (string) $request->getPassword());

        if (! $validUser || ! $validPassword) {
            return response('Acceso administrativo protegido.', 401, [
                'WWW-Authenticate' => 'Basic realm="Sorteos CR Admin"',
            ]);
        }

        return $next($request);
    }
}
