<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('staff.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:190'],
        ], [], ['login' => 'usuário ou e-mail', 'password' => 'senha']);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [$field => mb_strtolower(trim($data['login'])), 'password' => $data['password'], 'active' => true];

        if (! Auth::attempt($credentials)) {
            return back()->withInput($request->only('login'))->withErrors(['login' => 'Usuário ou senha inválidos.']);
        }

        $request->session()->regenerate();
        AuditLogger::log('login', 'user', Auth::id());

        /** @var User $user */
        $user = Auth::user();

        return redirect()->intended(route($user->homeRoute()));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}
