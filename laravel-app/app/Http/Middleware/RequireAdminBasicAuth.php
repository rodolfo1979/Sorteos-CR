<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(TenantContext::class)->current();
        $tenantHasCredentials = filled($tenant?->admin_username) && filled($tenant?->admin_password_hash);

        if ($tenantHasCredentials) {
            $validUser = hash_equals((string) $tenant->admin_username, (string) $request->getUser());
            $validPassword = Hash::check((string) $request->getPassword(), (string) $tenant->admin_password_hash);
        } else {
            $validUser = hash_equals((string) config('admin.username'), (string) $request->getUser());
            $validPassword = hash_equals((string) config('admin.password'), (string) $request->getPassword());
        }

        if (! $validUser || ! $validPassword) {
            return response('Acceso administrativo protegido.', 401, [
                'WWW-Authenticate' => 'Basic realm="Sorteos CR Admin"',
            ]);
        }

        return $next($request);
    }
}
