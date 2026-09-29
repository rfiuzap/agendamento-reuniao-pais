<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Mail\AppointmentMail;
use App\Models\Appointment;
use App\Models\Student;
use App\Models\TimeSlot;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppointmentService
{
    public const MSG_SLOT_TAKEN = 'Este horário acabou de ser reservado por outra pessoa. Escolha outro horário.';
    public const MSG_TIME_CONFLICT = 'Você já possui outro agendamento neste horário. Escolha outro horário.';
    public const MSG_STUDENT_DUPLICATE = 'Este aluno já possui um agendamento nesta reunião.';

    public function book(string $email, string $responsibleName, string $studentName, int $slotId): Appointment
    {
        $email = self::normalizeEmail($email);
        $studentName = self::normalizeName($studentName);
        $responsibleName = self::normalizeName($responsibleName);

        $appointment = $this->guardUnique(fn () => DB::transaction(function () use ($email, $responsibleName, $studentName, $slotId) {
            $slot = $this->lockSlot($slotId);
            $this->assertBookable($slot);

            $student = Student::firstOrCreate(['responsible_email' => $email, 'name' => $studentName]);

            $alreadyBooked = Appointment::active()
                ->where('meeting_id', $slot->meeting_id)
                ->where('student_id', $student->id)
                ->exists();
            if ($alreadyBooked) {
                throw new BusinessRuleException(self::MSG_STUDENT_DUPLICATE);
            }

            $this->assertNoTimeConflict($email, $slot);

            $appointment = Appointment::create([
                'meeting_id' => $slot->meeting_id,
                'time_slot_id' => $slot->id,
                'responsible_email' => $email,
                'responsible_name' => $responsibleName,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'status' => 'confirmed',
                'active_slot_id' => $slot->id,
                'active_meeting_id' => $slot->meeting_id,
            ]);

            $slot->update(['status' => 'booked']);

            return $appointment;
        }));

        $this->notify($appointment, 'confirmed');

        return $appointment;
    }

    /** Moves an active appointment to another slot of the same meeting, releasing the old one. */
    public function reschedule(Appointment $appointment, int $newSlotId, bool $sameClassOnly = true): Appointment
    {
        $appointment = $this->guardUnique(fn () => DB::transaction(function () use ($appointment, $newSlotId, $sameClassOnly) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointment->id);
            if (! $appointment->isActive()) {
                throw new BusinessRuleException('Este agendamento foi cancelado e não pode ser alterado.');
            }

            $old = $appointment->time_slot_id ? TimeSlot::lockForUpdate()->find($appointment->time_slot_id) : null;
            if ($old && $old->id === $newSlotId) {
                throw new BusinessRuleException('Escolha um horário diferente do atual.');
            }

            $new = $this->lockSlot($newSlotId);
            if ($new->meeting_id !== $appointment->meeting_id) {
                throw new BusinessRuleException('O novo horário deve pertencer à mesma reunião.');
            }
            if ($sameClassOnly && $old && $new->class_id !== $old->class_id) {
                throw new BusinessRuleException('O novo horário deve ser da mesma turma.');
            }
            $this->assertBookable($new);
            $this->assertNoTimeConflict($appointment->responsible_email, $new, $appointment->id);

            $appointment->update(['time_slot_id' => $new->id, 'active_slot_id' => $new->id]);
            $new->update(['status' => 'booked']);
            $old?->update(['status' => 'available']);

            return $appointment;
        }));

        $this->notify($appointment, 'updated');

        return $appointment;
    }

    public function cancel(Appointment $appointment): Appointment
    {
        $appointment = DB::transaction(function () use ($appointment) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointment->id);
            if (! $appointment->isActive()) {
                throw new BusinessRuleException('Este agendamento já está cancelado.');
            }

            $appointment->update(['status' => 'cancelled', 'active_slot_id' => null, 'active_meeting_id' => null]);

            if ($appointment->time_slot_id) {
                TimeSlot::whereKey($appointment->time_slot_id)->update(['status' => 'available']);
            }

            return $appointment;
        });

        $this->notify($appointment, 'cancelled');

        return $appointment;
    }

    /** Early validation for the booking wizard; book() repeats every check under lock. */
    public function precheck(string $email, string $studentName, TimeSlot $slot): void
    {
        $slot->loadMissing(['meeting', 'schoolClass']);
        $this->assertBookable($slot);

        $email = self::normalizeEmail($email);
        $student = Student::where('responsible_email', $email)->where('name', self::normalizeName($studentName))->first();
        if ($student && Appointment::active()->where('meeting_id', $slot->meeting_id)->where('student_id', $student->id)->exists()) {
            throw new BusinessRuleException(self::MSG_STUDENT_DUPLICATE);
        }

        $this->assertNoTimeConflict($email, $slot);
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function normalizeName(string $name): string
    {
        return preg_replace('/\s+/u', ' ', trim($name));
    }

    private function lockSlot(int $slotId): TimeSlot
    {
        $slot = TimeSlot::with(['meeting', 'schoolClass'])->lockForUpdate()->find($slotId);
        if (! $slot) {
            throw new BusinessRuleException('Horário não encontrado.');
        }

        return $slot;
    }

    private function assertBookable(TimeSlot $slot): void
    {
        if (! $slot->meeting->isBookable()) {
            throw new BusinessRuleException('Esta reunião não está disponível para agendamento.');
        }
        if (! $slot->schoolClass?->active) {
            throw new BusinessRuleException('Esta turma não está disponível para agendamento.');
        }
        if (! $slot->isAvailable()) {
            throw new BusinessRuleException(self::MSG_SLOT_TAKEN);
        }
    }

    /** RB08: the same responsible cannot hold two overlapping appointments on the same day. */
    private function assertNoTimeConflict(string $email, TimeSlot $slot, ?int $ignoreId = null): void
    {
        $conflict = Appointment::active()
            ->join('time_slots', 'time_slots.id', '=', 'appointments.time_slot_id')
            ->join('meetings', 'meetings.id', '=', 'appointments.meeting_id')
            ->where('appointments.responsible_email', $email)
            ->whereDate('meetings.date', $slot->meeting->date)
            ->where('time_slots.start_time', '<', $slot->end_time)
            ->where('time_slots.end_time', '>', $slot->start_time)
            ->when($ignoreId, fn ($q) => $q->where('appointments.id', '!=', $ignoreId))
            ->exists();

        if ($conflict) {
            throw new BusinessRuleException(self::MSG_TIME_CONFLICT);
        }
    }

    /** Unique index violations mean another request won the race for the slot or the student. */
    private function guardUnique(callable $callback): Appointment
    {
        try {
            return $callback();
        } catch (QueryException $e) {
            $sqlState = $e->errorInfo[0] ?? null;
            if ($sqlState === '23000' || str_contains($e->getMessage(), 'UNIQUE')) {
                throw new BusinessRuleException(
                    str_contains($e->getMessage(), 'active_meeting_id') ? self::MSG_STUDENT_DUPLICATE : self::MSG_SLOT_TAKEN
                );
            }
            throw $e;
        }
    }

    private function notify(Appointment $appointment, string $type): void
    {
        try {
            Mail::to($appointment->responsible_email)->send(new AppointmentMail($appointment->fresh(), $type));
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail de agendamento', ['appointment' => $appointment->id, 'error' => $e->getMessage()]);
        }
    }
}
