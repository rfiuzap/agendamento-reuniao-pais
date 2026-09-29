<?php

namespace Tests\Feature;

use App\Mail\AppointmentMail;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class CalendarAndEmailTest extends TestCase
{
    use CreatesSchoolData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function asParent(string $email): self
    {
        return $this->withSession(['parent.email' => $email, 'parent.expires_at' => time() + 3600]);
    }

    public function test_parent_downloads_calendar_event_of_own_appointment(): void
    {
        [$a] = $this->makeClasses($this->makeUser('teacher', 'maria', ['name' => 'Maria Souza']));
        $meeting = $this->makeMeeting([$a->id], ['date' => '2026-10-15']);
        $appointment = app(AppointmentService::class)->book('pai@x.com', 'Pai', 'Pedro Silva', $this->slot($meeting, $a, '08:30')->id);

        $response = $this->asParent('pai@x.com')->get(route('parent.appointments.calendar', $appointment));

        $response->assertOk()->assertHeader('content-type', 'text/calendar; charset=utf-8');
        $ics = $response->getContent();
        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('DTSTART:20261015T113000Z', $ics); // 08:30 em São Paulo
        $this->assertStringContainsString('DTEND:20261015T115500Z', $ics);
        $this->assertStringContainsString('SUMMARY:Reunião de Pais - Pedro Silva', $ics);
        $this->assertStringContainsString("\r\n", $ics);

        $this->asParent('outro@x.com')->get(route('parent.appointments.calendar', $appointment))->assertNotFound();
    }

    public function test_success_page_shows_add_to_calendar_buttons(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $appointment = app(AppointmentService::class)->book('pai@x.com', 'Pai', 'Pedro', $this->slot($meeting, $a, '08:00')->id);

        $this->asParent('pai@x.com')->get(route('parent.appointments.success', $appointment))
            ->assertSee('Adicionar à agenda do celular')
            ->assertSee(route('parent.appointments.calendar', $appointment))
            ->assertSee('calendar.google.com', false);
    }

    public function test_emails_for_confirmation_change_and_cancellation_carry_calendar_invite(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $service = app(AppointmentService::class);

        $appointment = $service->book('pai@x.com', 'Pai', 'Pedro', $this->slot($meeting, $a, '08:00')->id);
        $service->reschedule($appointment, $this->slot($meeting, $a, '09:00')->id);
        $service->cancel($appointment);

        foreach (['confirmed' => 'PUBLISH', 'updated' => 'PUBLISH', 'cancelled' => 'CANCEL'] as $type => $method) {
            Mail::assertSent(AppointmentMail::class, function (AppointmentMail $mail) use ($type, $method) {
                if ($mail->type !== $type || ! $mail->hasTo('pai@x.com')) {
                    return false;
                }
                $attachment = $mail->attachments()[0];
                $ics = $attachment->attachWith(fn () => null, fn ($data) => $data());

                return $attachment->as === 'reuniao-de-pais-'.$mail->appointment->id.'.ics'
                    && $attachment->mime === 'text/calendar; charset=utf-8; method='.$method
                    && str_contains($ics, 'METHOD:'.$method);
            });
        }

        $rendered = (new AppointmentMail($appointment->fresh(), 'updated'))->render();
        $this->assertStringContainsString('09:00 às 09:25', $rendered);
        $this->assertStringContainsString('Google Agenda', $rendered);
    }
}
