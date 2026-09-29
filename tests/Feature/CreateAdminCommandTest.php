<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private function runCommand(string $password, ?string $confirmation = null)
    {
        return $this->artisan('app:criar-admin')
            ->expectsQuestion('Nome', 'Diretoria')
            ->expectsQuestion('Usuário (login)', 'Admin')
            ->expectsQuestion('E-mail', 'diretoria@escola.test')
            ->expectsQuestion('Senha (mínimo 8 caracteres)', $password)
            ->expectsQuestion('Repita a senha', $confirmation ?? $password);
    }

    public function test_creates_admin_and_later_resets_password(): void
    {
        $this->runCommand('SenhaForte1')->assertSuccessful();

        $user = User::where('username', 'admin')->firstOrFail();
        $this->assertSame(User::ROLE_ADMIN, $user->role);
        $this->assertTrue(Hash::check('SenhaForte1', $user->password));

        $this->runCommand('OutraSenha2')->assertSuccessful();
        $this->assertTrue(Hash::check('OutraSenha2', $user->fresh()->password));
        $this->assertSame(1, User::count());
    }

    public function test_rejects_short_or_mismatched_password(): void
    {
        $this->runCommand('curta')->assertFailed();
        $this->runCommand('SenhaForte1', 'Diferente1')->assertFailed();
        $this->assertSame(0, User::count());
    }
}
