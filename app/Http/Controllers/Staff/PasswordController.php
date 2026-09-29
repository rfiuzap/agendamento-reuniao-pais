<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('staff.password', ['firstAccess' => $request->user()->must_change_password]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], ['password' => 'nova senha']);

        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'A nova senha deve ser diferente da senha atual.']);
        }

        $user->update(['password' => $data['password'], 'must_change_password' => false]);
        $request->session()->regenerate();

        return redirect()->route($user->homeRoute())->with('success', 'Senha alterada com sucesso.');
    }
}
