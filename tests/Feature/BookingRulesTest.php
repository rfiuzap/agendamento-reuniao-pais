<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Mail\AppointmentMail;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Services\AppointmentService;
use App\Services\MeetingService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class BookingRulesTest extends TestCase
{
    use CreatesSchoolData, RefreshDatabase;

    private AppointmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->service = app(AppointmentService::class);
    }

    public function test_slots_are_generated_independently_per_class(): void
    {
        [$a, $b] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id, $b->id]);

        $this->assertSame(4, TimeSlot::where('class_id', $a->id)->count());
        $this->assertSame(4, TimeSlot::where('class_id', $b->id)->count());

        $this->service->book('joao@x.com', 'João', 'Pedro', $this->slot($meeting, $a, '08:00')->id);

        $this->assertSame('booked', $this->slot($meeting, $a, '08:00')->status);
        $this->assertSame('available', $this->slot($meeting, $b, '08:00')->status);
        Mail::assertSent(AppointmentMail::class, fn ($m) => $m->type === 'confirmed' && $m->hasTo('joao@x.com'));
    }

    public function test_slot_accepts_only_one_active_appointment(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $slot = $this->slot($meeting, $a, '08:00');

        $this->service->book('joao@x.com', 'João', 'Pedro', $slot->id);

        $this->expectExceptionMessage(AppointmentService::MSG_SLOT_TAKEN);
        $this->service->book('maria@x.com', 'Maria', 'Ana', $slot->id);
    }

    public function test_database_rejects_second_active_appointment_even_if_interface_is_bypassed(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $slot = $this->slot($meeting, $a, '08:00');
        $first = $this->service->book('joao@x.com', 'João', 'Pedro', $slot->id);

        $this->expectException(QueryException::class);
        Appointment::create(array_merge($first->only([
            'meeting_id', 'time_slot_id', 'responsible_email', 'responsible_name', 'student_id', 'student_name',
        ]), ['status' => 'confirmed', 'active_slot_id' => $slot->id, 'student_name' => 'Outro']));
    }

    public function test_race_where_slot_status_is_stale_is_resolved_by_unique_index(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $slot = $this->slot($meeting, $a, '08:00');
        $this->service->book('joao@x.com', 'João', 'Pedro', $slot->id);

        // Simulates a concurrent request that read the slot as available.
        $slot->update(['status' => 'available']);

        try {
            $this->service->book('maria@x.com', 'Maria', 'Ana', $slot->id);
            $this->fail('Second booking should fail');
        } catch (BusinessRuleException $e) {
            $this->assertSame(AppointmentService::MSG_SLOT_TAKEN, $e->getMessage());
        }
        $this->assertSame(1, Appointment::active()->count());
    }

    public function test_same_responsible_can_book_several_children(): void
    {
        [$a, $b] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id, $b->id]);

        $this->service->book('joao@x.com', 'João Silva', 'Pedro Silva', $this->slot($meeting, $b, '08:00')->id);
        $this->service->book('joao@x.com', 'João Silva', 'Maria Silva', $this->slot($meeting, $a, '09:00')->id);

        $this->assertSame(2, Appointment::active()->where('responsible_email', 'joao@x.com')->count());
    }

    public function test_same_responsible_cannot_have_two_children_at_same_time(): void
    {
        [$a, $b] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id, $b->id]);
        $this->service->book('joao@x.com', 'João', 'Pedro', $this->slot($meeting, $b, '08:00')->id);

        $this->expectExceptionMessage('Você já possui outro agendamento neste horário. Escolha outro horário.');
        $this->service->book('JOAO@x.com ', 'João', 'Maria', $this->slot($meeting, $a, '08:00')->id);
    }

    public function test_same_child_cannot_be_booked_twice_in_same_meeting(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $this->service->book('joao@x.com', 'João', 'Pedro Silva', $this->slot($meeting, $a, '08:00')->id);

        $this->expectExceptionMessage(AppointmentService::MSG_STUDENT_DUPLICATE);
        $this->service->book('joao@x.com', 'João', '  Pedro   Silva ', $this->slot($meeting, $a, '09:00')->id);
    }

    public function test_reschedule_releases_old_slot_and_books_new(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $appointment = $this->service->book('joao@x.com', 'João', 'Pedro', $this->slot($meeting, $a, '08:00')->id);

        $this->service->reschedule($appointment, $this->slot($meeting, $a, '09:30')->id);

        $this->assertSame('available', $this->slot($meeting, $a, '08:00')->status);
        $this->assertSame('booked', $this->slot($meeting, $a, '09:30')->status);
        $this->assertSame($this->slot($meeting, $a, '09:30')->id, $appointment->fresh()->time_slot_id);
        Mail::assertSent(AppointmentMail::class, fn ($m) => $m->type === 'updated');
    }

    public function test_reschedule_to_taken_slot_fails(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $mine = $this->service->book('joao@x.com', 'João', 'Pedro', $this->slot($meeting, $a, '08:00')->id);
        $this->service->book('maria@x.com', 'Maria', 'Ana', $this->slot($meeting, $a, '08:30')->id);

        $this->expectExceptionMessage(AppointmentService::MSG_SLOT_TAKEN);
        $this->service->reschedule($mine, $this->slot($meeting, $a, '08:30')->id);
    }

    public function test_cancel_releases_slot_and_allows_new_booking(): void
    {
        [$a] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id]);
        $slot = $this->slot($meeting, $a, '08:00');
        $appointment = $this->service->book('joao@x.com', 'João', 'Pedro', $slot->id);

        $this->service->cancel($appointment);

        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertSame('available', $slot->fresh()->status);
        Mail::assertSent(AppointmentMail::class, fn ($m) => $m->type === 'cancelled');

        $this->service->book('maria@x.com', 'Maria', 'Ana', $slot->id);
        $this->service->book('joao@x.com', 'João', 'Pedro', $this->slot($meeting, $a, '09:00')->id);
        $this->assertSame(2, Appointment::active()->count());
    }

    public function test_cannot_book_unpublished_or_past_meeting(): void
    {
        [$a, $b] = $this->makeClasses();
        $draft = $this->makeMeeting([$a->id], ['status' => 'draft']);
        $past = $this->makeMeeting([$b->id], ['date' => now()->subDay()->toDateString()]);

        foreach ([[$draft, $a], [$past, $b]] as [$meeting, $class]) {
            try {
                $this->service->book('joao@x.com', 'João', 'Pedro', $this->slot($meeting, $class, '08:00')->id);
                $this->fail('Booking should be refused');
            } catch (BusinessRuleException $e) {
                $this->assertStringContainsString('não está disponível', $e->getMessage());
            }
        }
    }

    public function test_meeting_update_regenerates_free_slots_but_protects_booked_ones(): void
    {
        [$a, $b] = $this->makeClasses();
        $meeting = $this->makeMeeting([$a->id, $b->id]);
        $this->service->book('joao@x.com', 'João', 'Pedro', $this->slot($meeting, $a, '09:30')->id);
        $data = $meeting->only(['name', 'start_time', 'end_time', 'duration_minutes', 'break_minutes', 'status']);
        $data['date'] = $meeting->date->toDateString();

        // Removing class B (no bookings) and extending the end time is allowed.
        app(MeetingService::class)->save($meeting, array_merge($data, ['end_time' => '11:00']), [$a->id]);
        $this->assertSame(0, TimeSlot::where('class_id', $b->id)->count());
        $this->assertSame(6, TimeSlot::where('class_id', $a->id)->count());

        // Shrinking so that the booked 09:30 slot disappears is refused.
        $this->expectException(BusinessRuleException::class);
        app(MeetingService::class)->save($meeting->fresh(), array_merge($data, ['end_time' => '09:00']), [$a->id]);
    }
}
