<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'app:criar-admin';

    protected $description = 'Cria (ou redefine a senha de) um usuário administrador';

    public function handle(): int
    {
        $data = [
            'name' => $this->ask('Nome'),
            'username' => mb_strtolower(trim((string) $this->ask('Usuário (login)', 'admin'))),
            'email' => mb_strtolower(trim((string) $this->ask('E-mail'))),
            'password' => $this->secret('Senha (mínimo 8 caracteres)'),
        ];
        $data['password_confirmation'] = $this->secret('Repita a senha');

        $existing = User::where('username', $data['username'])->first();

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'alpha_dash', 'max:60'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'.($existing ? ','.$existing->id : '')],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::updateOrCreate(['username' => $data['username']], [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => User::ROLE_ADMIN,
            'active' => true,
        ]);

        $this->info(($existing ? 'Senha redefinida' : 'Administrador criado').": {$data['username']}");

        return self::SUCCESS;
    }
}
