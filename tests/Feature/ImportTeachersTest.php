<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportTeachersTest extends TestCase
{
    use RefreshDatabase;

    private function file(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'prof');
        file_put_contents($path, $content);

        return $path;
    }

    public function test_imports_rooms_classes_and_teachers_with_temporary_password(): void
    {
        $file = $this->file(implode("\n", [
            'G1 - berçário manhã - Ana Teste - ana@escola-exemplo.com.br',
            'G2 - A manhã - Bia Teste -bia@escola-exemplo.com.br',
            'G2 -A tarde - Ana Teste - ana@escola-exemplo.com.br',
            'linha inválida',
        ]));

        $this->artisan('app:importar-professoras', ['arquivo' => $file])->assertSuccessful();

        $this->assertSame(['G1', 'G2'], SchoolYear::orderBy('name')->pluck('name')->all());
        $this->assertSame(['A manhã', 'berçário manhã'], SchoolClass::orderBy('name')->pluck('name')->all());

        $ana = User::where('email', 'ana@escola-exemplo.com.br')->firstOrFail();
        $this->assertSame('ana', $ana->username);
        $this->assertSame(User::ROLE_TEACHER, $ana->role);
        $this->assertTrue($ana->must_change_password);
        $this->assertTrue(Hash::check('morumbi', $ana->password));
        $this->assertSame('bia', User::where('email', 'bia@escola-exemplo.com.br')->value('username'));
        $this->assertSame($ana->id, SchoolClass::where('name', 'berçário manhã')->value('teacher_id'));
    }

    public function test_same_class_name_in_different_rooms_is_allowed_but_not_in_the_same_room(): void
    {
        $file = $this->file("1º ano - A manhã - Ana Teste - ana@escola-exemplo.com.br\n2º ano - A manhã - Bia Teste - bia@escola-exemplo.com.br");
        $this->artisan('app:importar-professoras', ['arquivo' => $file])->assertSuccessful();

        $classes = SchoolClass::with('schoolYear')->orderBy('school_year_id')->get();
        $this->assertSame(['A manhã', 'A manhã'], $classes->pluck('name')->all());
        $this->assertSame(['1º ano A manhã', '2º ano A manhã'], $classes->map->fullName()->all());

        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'adm@escola-exemplo.com.br', 'username' => 'adm', 'password' => 'x', 'role' => 'admin', 'active' => true]));
        $this->post(route('staff.classes.store'), ['school_year_id' => $classes[0]->school_year_id, 'name' => 'A manhã', 'active' => '1'])
            ->assertSessionHasErrors('name');
        $this->post(route('staff.classes.store'), ['school_year_id' => $classes[0]->school_year_id, 'name' => 'B manhã', 'active' => '1'])
            ->assertSessionHasNoErrors();
    }

    public function test_simulation_writes_nothing_and_rerun_keeps_existing_password(): void
    {
        $file = $this->file('G3 - A tarde - Carla Teste - carla@escola-exemplo.com.br');

        $this->artisan('app:importar-professoras', ['arquivo' => $file, '--simular' => true])->assertSuccessful();
        $this->assertSame(0, User::count());

        $this->artisan('app:importar-professoras', ['arquivo' => $file])->assertSuccessful();
        $carla = User::firstOrFail();
        $carla->update(['password' => 'SenhaPessoal1', 'must_change_password' => false]);

        $this->artisan('app:importar-professoras', ['arquivo' => $file])->assertSuccessful();
        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('SenhaPessoal1', $carla->fresh()->password));
        $this->assertFalse($carla->fresh()->must_change_password);
    }
}
