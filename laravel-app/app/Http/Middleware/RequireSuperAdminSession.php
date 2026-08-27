<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSuperAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('superadmin_authenticated') !== true) {
            return redirect()->route('superadmin.login');
        }

        return $next($request);
    }
}
