<?php

namespace App\Http\Controllers\Parent;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureParentAuthenticated as ParentSession;
use App\Services\AppointmentService;
use App\Services\ParentAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const PENDING_EMAIL = 'parent.pending_email';

    public function __construct(private ParentAuthService $auth)
    {
    }

    public function showEmail(Request $request): View|RedirectResponse
    {
        if ($request->session()->get(ParentSession::SESSION_EXPIRES, 0) > time()) {
            return redirect()->route('parent.home');
        }

        return view('parent.login');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:190']]);
        $email = AppointmentService::normalizeEmail($data['email']);
        $request->session()->put(self::PENDING_EMAIL, $email);

        try {
            $this->auth->sendCode($email);
        } catch (BusinessRuleException $e) {
            return redirect()->route('parent.code')->with('warning', $e->getMessage());
        }

        return redirect()->route('parent.code')->with('success', 'Enviamos um código de 6 dígitos para o seu e-mail.');
    }

    public function showCode(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get(self::PENDING_EMAIL);
        if (! $email) {
            return redirect()->route('parent.login');
        }

        return view('parent.code', ['email' => $email]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::PENDING_EMAIL);
        if (! $email) {
            return redirect()->route('parent.login');
        }

        $data = $request->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'O código deve ter 6 números.']);

        try {
            $this->auth->verify($email, $data['code']);
        } catch (BusinessRuleException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $request->session()->regenerate();
        $request->session()->forget(self::PENDING_EMAIL);
        $request->session()->put([
            ParentSession::SESSION_EMAIL => $email,
            ParentSession::SESSION_EXPIRES => time() + config('reuniao.parent_session_minutes') * 60,
        ]);

        return redirect()->route('parent.home');
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::PENDING_EMAIL);
        if (! $email) {
            return redirect()->route('parent.login');
        }

        try {
            $this->auth->sendCode($email);
        } catch (BusinessRuleException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', 'Enviamos um novo código para o seu e-mail.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([ParentSession::SESSION_EMAIL, ParentSession::SESSION_EXPIRES, 'booking']);
        $request->session()->regenerate(true);

        return redirect()->route('parent.login')->with('success', 'Você saiu do sistema.');
    }
}
