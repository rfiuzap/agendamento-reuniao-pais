<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Meeting;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class AuditHistoryTest extends TestCase
{
    use CreatesSchoolData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::forget('settings');
    }

    private function lastChanges(string $entity): array
    {
        return AuditLog::where('entity', $entity)->latest('id')->firstOrFail()->changes();
    }

    public function test_records_each_changed_field_with_before_and_after_and_author(): void
    {
        $admin = $this->makeUser('admin', 'chefe', ['name' => 'Diretoria']);
        $maria = $this->makeUser('teacher', 'maria', ['name' => 'Maria Souza']);
        $ana = $this->makeUser('teacher', 'ana', ['name' => 'Ana Lima']);
        [$a, $b] = $this->makeClasses($maria);
        $this->actingAs($admin);

        $this->put(route('staff.classes.update', $a), [
            'school_year_id' => $a->school_year_id, 'name' => '5º Ano A1', 'teacher_id' => $ana->id, 'active' => '1',
        ]);

        $log = AuditLog::where('entity', 'class')->latest('id')->first();
        $this->assertSame('Diretoria', $log->actor);
        $this->assertSame('updated', $log->action);
        $this->assertEqualsCanonicalizing([
            ['campo' => 'Nome', 'antes' => '5º Ano A', 'depois' => '5º Ano A1'],
            ['campo' => 'Professora', 'antes' => 'Maria Souza', 'depois' => 'Ana Lima'],
        ], $log->changes());
    }

    public function test_meeting_status_and_classes_changes_are_readable(): void
    {
        $this->actingAs($this->makeUser('admin', 'chefe'));
        [$a, $b] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id], ['status' => 'draft']);

        $this->put(route('staff.meetings.update', $meeting), [
            'name' => $meeting->name, 'date' => $meeting->date->toDateString(), 'start_time' => '08:00', 'end_time' => '10:00',
            'duration_minutes' => 25, 'break_minutes' => 5, 'status' => 'published', 'class_ids' => [$a->id, $b->id],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([
            ['campo' => 'Status', 'antes' => 'Rascunho', 'depois' => 'Liberada'],
            ['campo' => 'Turmas participantes', 'antes' => '5º Ano A', 'depois' => '5º Ano A, 5º Ano B'],
        ], $this->lastChanges('meeting'));
    }

    public function test_password_value_is_never_logged(): void
    {
        $admin = $this->makeUser('admin', 'chefe');
        $this->actingAs($admin);
        $user = $this->makeUser('coordinator', 'coord');

        $this->put(route('staff.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'username' => 'coord', 'role' => 'coordinator',
            'password' => 'NovaSenha123', 'password_confirmation' => 'NovaSenha123', 'active' => '1',
        ]);

        $this->assertSame([['campo' => 'Senha', 'antes' => '', 'depois' => '(alterada)']], $this->lastChanges('user'));
        $this->assertStringNotContainsString('NovaSenha123', AuditLog::all()->toJson());
    }

    public function test_parent_actions_are_attributed_to_the_parent_email(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $appointment = app(AppointmentService::class)->book('pai@x.com', 'Pai', 'Pedro', $this->slot($meeting, $a, '08:00')->id);

        $this->withSession(['parent.email' => 'pai@x.com', 'parent.expires_at' => time() + 3600])
            ->post(route('parent.appointments.cancel.store', $appointment));

        $log = AuditLog::where('entity', 'appointment')->latest('id')->first();
        $this->assertSame('pai@x.com (responsável)', $log->actor);
        $this->assertSame([['campo' => 'Status', 'antes' => 'Confirmado', 'depois' => 'Cancelado']], $log->changes());
    }

    public function test_settings_and_history_page(): void
    {
        $this->actingAs($this->makeUser('admin', 'chefe', ['name' => 'Diretoria']));
        $this->put(route('staff.settings.update'), ['school_name' => 'Colégio Novo', 'contact_phone' => '11 5555-0000']);

        $this->assertEqualsCanonicalizing([
            ['campo' => 'Nome da escola', 'antes' => 'Colégio Morumbi', 'depois' => 'Colégio Novo'],
            ['campo' => 'Telefone de contato', 'antes' => '', 'depois' => '11 5555-0000'],
        ], $this->lastChanges('settings'));

        $this->get(route('staff.reports.logs'))->assertOk()
            ->assertSee('Alteração · Configurações')
            ->assertSee('Nome da escola:')
            ->assertSee('<del>Colégio Morumbi</del>', false)
            ->assertSee('por Diretoria');
    }
}
