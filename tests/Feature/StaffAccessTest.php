<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Meeting;
use App\Models\TimeSlot;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use CreatesSchoolData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_login_with_username_and_inactive_user_is_blocked(): void
    {
        $this->makeUser('admin', 'chefe');
        $this->makeUser('coordinator', 'inativa', ['active' => false]);

        $this->post(route('staff.login.store'), ['login' => 'chefe', 'password' => 'senha123'])
            ->assertRedirect(route('staff.dashboard'));
        $this->post(route('staff.logout'))->assertRedirect(route('parent.login'));

        $this->post(route('staff.login.store'), ['login' => 'inativa', 'password' => 'senha123'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('staff.dashboard'))->assertRedirect(route('staff.login'));
        $this->get(route('staff.appointments.index'))->assertRedirect(route('staff.login'));
    }

    public function test_admin_pages_render(): void
    {
        $admin = $this->makeUser('admin', 'chefe');
        [$a, $b] = $this->makeClasses($this->makeUser('teacher', 'maria'));
        $meeting = $this->makeMeeting([$a->id, $b->id]);
        $appointment = app(AppointmentService::class)->book('pai@x.com', 'Pai', 'Filho', $this->slot($meeting, $a, '08:00')->id);

        $this->actingAs($admin);
        foreach ([
            route('staff.dashboard'), route('staff.dashboard', ['meeting_id' => $meeting->id, 'class_id' => $a->id]),
            route('staff.meetings.index'), route('staff.meetings.show', $meeting), route('staff.meetings.create'),
            route('staff.meetings.edit', $meeting), route('staff.years.index'), route('staff.years.create'),
            route('staff.classes.index'), route('staff.classes.create'), route('staff.teachers.index'),
            route('staff.teachers.create'), route('staff.users.index'), route('staff.users.create'),
            route('staff.appointments.index'), route('staff.appointments.index', ['group' => 'class']),
            route('staff.appointments.edit', $appointment), route('staff.reports.index'),
            route('staff.reports.logs'), route('staff.settings.edit'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_creates_meeting_with_generated_slots(): void
    {
        $this->actingAs($this->makeUser('admin', 'chefe'));
        [$a, $b] = $this->makeClasses();

        $this->post(route('staff.meetings.store'), [
            'name' => 'Reunião de Pais - 2º Semestre', 'date' => '2026-10-15',
            'start_time' => '08:00', 'end_time' => '13:00', 'duration_minutes' => 25, 'break_minutes' => 5,
            'status' => 'published', 'class_ids' => [$a->id, $b->id],
        ])->assertRedirect();

        $meeting = Meeting::firstOrFail();
        $this->assertSame(20, TimeSlot::where('meeting_id', $meeting->id)->count());
        $this->assertTrue(AuditLog::where('entity', 'meeting')->where('action', 'created')->exists());
    }

    public function test_coordinator_sees_all_appointments_but_cannot_administer(): void
    {
        [$a, $b] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id, $b->id]);
        $service = app(AppointmentService::class);
        $service->book('pai1@x.com', 'Pai Um', 'Aluno Alfa', $this->slot($meeting, $a, '08:00')->id);
        $service->book('pai2@x.com', 'Pai Dois', 'Aluno Beta', $this->slot($meeting, $b, '08:00')->id);

        $this->actingAs($this->makeUser('coordinator', 'coord'));
        $this->get(route('staff.appointments.index'))->assertOk()->assertSee('Aluno Alfa')->assertSee('Aluno Beta');
        $this->get(route('staff.appointments.index', ['class_id' => $b->id]))->assertSee('Aluno Beta')->assertDontSee('Aluno Alfa');
        $this->get(route('staff.meetings.show', $meeting))->assertOk();

        $this->get(route('staff.reports.index', ['meeting_id' => $meeting->id]))->assertOk()
            ->assertSee('Aluno Alfa')->assertDontSee('Histórico de alterações');
        $this->get(route('staff.reports.logs'))->assertForbidden();

        $this->get(route('staff.dashboard'))->assertForbidden();
        $this->get(route('staff.meetings.create'))->assertForbidden();
        $this->get(route('staff.users.index'))->assertForbidden();
        $this->get(route('staff.settings.edit'))->assertForbidden();
        $this->delete(route('staff.meetings.destroy', $meeting))->assertForbidden();
    }

    public function test_teacher_only_sees_own_classes(): void
    {
        $maria = $this->makeUser('teacher', 'maria');
        $ana = $this->makeUser('teacher', 'ana');
        [$a, $b] = $this->makeClasses($maria, $ana);
        $meeting = $this->makeMeeting([$a->id, $b->id]);
        $service = app(AppointmentService::class);
        $service->book('pai1@x.com', 'Pai Um', 'Aluno Alfa', $this->slot($meeting, $a, '08:00')->id);
        $service->book('pai2@x.com', 'Pai Dois', 'Aluno Beta', $this->slot($meeting, $b, '08:00')->id);

        $this->actingAs($maria);
        $this->get(route('staff.teacher.dashboard'))->assertOk()
            ->assertSee('Aluno Alfa')->assertSee('Pai Um')
            ->assertDontSee('Aluno Beta');
        $this->get(route('staff.teacher.dashboard', ['class_id' => $b->id]))->assertDontSee('Aluno Beta');

        $this->get(route('staff.appointments.index'))->assertForbidden();
        $this->get(route('staff.meetings.show', $meeting))->assertForbidden();
    }

    public function test_admin_can_cancel_and_move_appointment_to_another_class(): void
    {
        [$a, $b] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id, $b->id]);
        $appointment = app(AppointmentService::class)->book('pai@x.com', 'Pai', 'Filho', $this->slot($meeting, $a, '08:00')->id);
        $this->actingAs($this->makeUser('admin', 'chefe'));

        $this->put(route('staff.appointments.update', $appointment), ['slot_id' => $this->slot($meeting, $b, '09:00')->id])
            ->assertRedirect(route('staff.appointments.index'));
        $this->assertSame('booked', $this->slot($meeting, $b, '09:00')->status);

        $this->post(route('staff.appointments.cancel', $appointment));
        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertSame(['created', 'updated', 'updated'], AuditLog::where('entity', 'appointment')->orderBy('id')->pluck('action')->all());
    }

    public function test_output_is_escaped_against_xss(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        app(AppointmentService::class)->book('pai@x.com', 'Pai', '<script>alert(1)</script>', $this->slot($meeting, $a, '08:00')->id);
        $this->actingAs($this->makeUser('admin', 'chefe'));

        $this->get(route('staff.appointments.index'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
