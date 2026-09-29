<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Staff with a temporary password (first access) must choose a new one before using the system. */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password && ! $request->routeIs('staff.password.*', 'staff.logout')) {
            return redirect()->route('staff.password.edit');
        }

        return $next($request);
    }
}
