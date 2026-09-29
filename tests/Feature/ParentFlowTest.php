<?php

namespace Tests\Feature;

use App\Mail\AccessCodeMail;
use App\Mail\AppointmentMail;
use App\Models\Appointment;
use App\Models\AuthenticationCode;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class ParentFlowTest extends TestCase
{
    use CreatesSchoolData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function requestCode(string $email = 'pai@x.com'): string
    {
        $this->post(route('parent.code.send'), ['email' => $email])->assertRedirect(route('parent.code'));

        $code = null;
        Mail::assertSent(AccessCodeMail::class, function (AccessCodeMail $mail) use (&$code, $email) {
            $code = $mail->code;

            return $mail->hasTo(mb_strtolower($email));
        });

        return $code;
    }

    private function login(string $email = 'pai@x.com'): void
    {
        $code = $this->requestCode($email);
        $this->post(route('parent.code.verify'), ['code' => $code]);
    }

    public function test_home_page_shows_email_form(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Agendamento de Reunião de Pais')
            ->assertSee('Para agendar a reunião com a professora, informe seu e-mail.')->assertSee('Login da equipe escolar')
            ->assertSee('Continuar');
    }

    public function test_code_is_stored_hashed_and_authenticates(): void
    {
        $code = $this->requestCode();

        $record = AuthenticationCode::first();
        $this->assertNotSame($code, $record->code_hash);
        $this->assertStringNotContainsString($code, $record->code_hash);
        $this->assertTrue($record->expires_at->between(now()->addMinutes(9), now()->addMinutes(10)));

        $this->post(route('parent.code.verify'), ['code' => $code])->assertRedirect(route('parent.home'));
        $this->assertNotNull($record->fresh()->used_at);
    }

    public function test_code_cannot_be_reused(): void
    {
        $code = $this->requestCode();
        $this->post(route('parent.code.verify'), ['code' => $code]);
        $this->post(route('parent.logout'));

        $this->withSession(['parent.pending_email' => 'pai@x.com'])
            ->post(route('parent.code.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_expired_code_is_rejected(): void
    {
        $code = $this->requestCode();
        $this->travel(11)->minutes();

        $this->post(route('parent.code.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->get(route('parent.home'))->assertRedirect(route('parent.login'));
    }

    public function test_attempts_are_limited(): void
    {
        $code = $this->requestCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('parent.code.verify'), ['code' => $wrong])->assertSessionHasErrors('code');
        }

        $this->post(route('parent.code.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertSame(5, AuthenticationCode::first()->attempts);
    }

    public function test_new_code_invalidates_previous_one(): void
    {
        $first = $this->requestCode();
        $this->travel(61)->seconds();
        $this->post(route('parent.code.resend'));

        $this->post(route('parent.code.verify'), ['code' => $first])->assertSessionHasErrors('code');
    }

    public function test_protected_pages_require_authentication(): void
    {
        $this->get(route('parent.booking.meetings'))->assertRedirect(route('parent.login'));
    }

    public function test_alert_when_no_meeting_is_available(): void
    {
        $this->login();
        $this->get(route('parent.home'))->assertRedirect(route('parent.booking.meetings'));
        $this->get(route('parent.booking.meetings'))->assertSee('Nenhuma reunião liberada');
    }

    public function test_full_booking_wizard(): void
    {
        [$a, $b] = $this->makeClasses($this->makeUser('teacher', 'maria'));
        $meeting = $this->makeMeeting([$a->id, $b->id]);
        $this->login('Joao@X.com');

        $this->get(route('parent.booking.meetings'))->assertSee($meeting->name);
        $this->get(route('parent.booking.details', $meeting))->assertOk()->assertSee('5º Ano A');

        $this->post(route('parent.booking.details.store', $meeting), [
            'school_year_id' => $a->school_year_id,
            'class_id' => $a->id,
            'responsible_name' => 'João Silva',
            'student_name' => 'Pedro Silva',
        ])->assertRedirect(route('parent.booking.slots', $meeting));

        $slot = $this->slot($meeting, $a, '08:30');
        $this->get(route('parent.booking.slots', $meeting))->assertOk()->assertSee('08:30')->assertSee('Disponível');
        $this->post(route('parent.booking.slots.select', $meeting), ['slot_id' => $slot->id])
            ->assertRedirect(route('parent.booking.summary', $meeting));
        $this->get(route('parent.booking.summary', $meeting))->assertSee('Pedro Silva')->assertSee('08:30');

        $response = $this->post(route('parent.booking.confirm', $meeting));
        $appointment = Appointment::firstOrFail();
        $response->assertRedirect(route('parent.appointments.success', $appointment));

        $this->assertSame('joao@x.com', $appointment->responsible_email);
        Mail::assertSent(AppointmentMail::class);

        $this->get(route('parent.appointments.success', $appointment))->assertSee('Tem mais de um filho?');
        $this->get(route('parent.home'))->assertSee('Você já possui um agendamento.')
            ->assertSee('ALTERAR')->assertSee('CANCELAR')->assertSee('Adicionar outro filho');
    }

    public function test_slot_screen_marks_reserved_slots_and_shows_conflict_message(): void
    {
        [$a, $b] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id, $b->id]);
        app(AppointmentService::class)->book('pai@x.com', 'Pai', 'Filho Um', $this->slot($meeting, $b, '08:00')->id);
        $this->login();

        $this->post(route('parent.booking.details.store', $meeting), [
            'school_year_id' => $a->school_year_id, 'class_id' => $a->id,
            'responsible_name' => 'Pai', 'student_name' => 'Filho Dois',
        ]);

        $this->post(route('parent.booking.slots.select', $meeting), ['slot_id' => $this->slot($meeting, $a, '08:00')->id])
            ->assertSessionHas('error', 'Você já possui outro agendamento neste horário. Escolha outro horário.');
    }

    public function test_parent_cannot_touch_someone_elses_appointment(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $other = app(AppointmentService::class)->book('outro@x.com', 'Outro', 'Aluno', $this->slot($meeting, $a, '08:00')->id);
        $this->login();

        $this->get(route('parent.appointments.edit', $other))->assertNotFound();
        $this->post(route('parent.appointments.cancel.store', $other))->assertNotFound();
        $this->assertTrue($other->fresh()->isActive());
    }

    public function test_parent_can_reschedule_and_cancel(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $mine = app(AppointmentService::class)->book('pai@x.com', 'Pai', 'Filho', $this->slot($meeting, $a, '08:00')->id);
        $this->login();

        $this->get(route('parent.appointments.edit', $mine))->assertOk()->assertSee('Atual');
        $this->post(route('parent.appointments.update', $mine), ['slot_id' => $this->slot($meeting, $a, '09:00')->id])
            ->assertRedirect(route('parent.home'));
        $this->assertSame('available', $this->slot($meeting, $a, '08:00')->status);

        $this->get(route('parent.appointments.cancel', $mine))->assertSee('Deseja realmente cancelar este agendamento?');
        $this->post(route('parent.appointments.cancel.store', $mine))->assertRedirect(route('parent.home'));
        $this->assertSame('cancelled', $mine->fresh()->status);
        $this->assertSame('available', $this->slot($meeting, $a, '09:00')->status);
    }
}
