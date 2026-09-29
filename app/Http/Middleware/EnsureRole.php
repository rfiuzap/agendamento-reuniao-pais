<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->active) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('staff.login')->withErrors(['login' => 'Sua sessão expirou ou o usuário está inativo.']);
        }

        abort_unless($user->hasRole(...$roles), 403, 'Você não tem permissão para acessar esta página.');

        return $next($request);
    }
}
