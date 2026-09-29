<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use CreatesSchoolData, RefreshDatabase;

    public function test_first_access_forces_password_change(): void
    {
        $teacher = $this->makeUser('teacher', 'maria', ['password' => 'morumbi', 'must_change_password' => true]);

        $this->post(route('staff.login.store'), ['login' => 'maria', 'password' => 'morumbi']);
        $this->get(route('staff.teacher.dashboard'))->assertRedirect(route('staff.password.edit'));
        $this->get(route('staff.password.edit'))->assertOk()->assertSee('primeiro acesso');

        $this->put(route('staff.password.update'), ['password' => 'morumbi', 'password_confirmation' => 'morumbi'])
            ->assertSessionHasErrors('password');
        $this->put(route('staff.password.update'), ['password' => 'NovaSenha9', 'password_confirmation' => 'NovaSenha9'])
            ->assertRedirect(route('staff.teacher.dashboard'));

        $this->assertFalse($teacher->fresh()->must_change_password);
        $this->assertTrue(Hash::check('NovaSenha9', $teacher->fresh()->password));
        $this->get(route('staff.teacher.dashboard'))->assertOk();
    }

    public function test_admin_can_change_user_email_and_reset_makes_password_temporary(): void
    {
        $this->actingAs($this->makeUser('admin', 'chefe'));
        $teacher = $this->makeUser('teacher', 'maria');

        $this->put(route('staff.teachers.update', $teacher), [
            'name' => $teacher->name, 'email' => 'Maria.Nova@Escola.test', 'username' => 'maria',
            'password' => 'Temporaria1', 'password_confirmation' => 'Temporaria1', 'active' => '1',
        ])->assertSessionHasNoErrors();

        $teacher->refresh();
        $this->assertSame('maria.nova@escola.test', $teacher->email);
        $this->assertTrue($teacher->must_change_password);

        $coord = $this->makeUser('coordinator', 'coord');
        $this->put(route('staff.users.update', $coord), [
            'name' => $coord->name, 'email' => 'coordenacao@escola.test', 'username' => 'coord', 'role' => 'coordinator', 'active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('coordenacao@escola.test', $coord->fresh()->email);
        $this->assertFalse($coord->fresh()->must_change_password);
    }
}
