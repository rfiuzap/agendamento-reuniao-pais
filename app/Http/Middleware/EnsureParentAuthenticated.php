<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentAuthenticated
{
    public const SESSION_EMAIL = 'parent.email';
    public const SESSION_EXPIRES = 'parent.expires_at';

    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        $expires = (int) $request->session()->get(self::SESSION_EXPIRES, 0);

        if (! $email || $expires < time()) {
            $request->session()->forget([self::SESSION_EMAIL, self::SESSION_EXPIRES]);

            return redirect()->route('parent.login')->with('warning', 'Sua sessão expirou. Informe seu e-mail novamente.');
        }

        // Sliding expiration
        $request->session()->put(self::SESSION_EXPIRES, time() + config('reuniao.parent_session_minutes') * 60);
        $request->attributes->set('parent_email', $email);

        return $next($request);
    }
}
