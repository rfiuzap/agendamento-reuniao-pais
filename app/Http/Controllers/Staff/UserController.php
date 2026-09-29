<?php

namespace App\Http\Controllers\Staff;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->query('role'), fn ($q, $r) => $q->where('role', $r))
            ->orderBy('role')->orderBy('name')
            ->get();

        return view('staff.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('staff.users.form', ['user' => new User(['active' => true, 'role' => User::ROLE_COORDINATOR])]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validated($request));

        return redirect()->route('staff.users.index')->with('success', 'Usuário criado.');
    }

    public function edit(User $user): View
    {
        return view('staff.users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($user->id === $request->user()->id && ($data['role'] !== User::ROLE_ADMIN || ! $data['active'])) {
            throw new BusinessRuleException('Você não pode remover seu próprio acesso de administrador.');
        }

        $user->update($data);

        return redirect()->route('staff.users.index')->with('success', 'Usuário atualizado.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'username' => mb_strtolower(trim((string) $request->input('username'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user)],
            'username' => ['required', 'alpha_dash', 'max:60', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ], [], ['name' => 'nome', 'username' => 'usuário', 'password' => 'senha', 'role' => 'perfil']);

        $data['active'] = $request->boolean('active');
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
