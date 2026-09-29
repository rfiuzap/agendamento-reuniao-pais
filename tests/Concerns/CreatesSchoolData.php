<?php

namespace Tests\Concerns;

use App\Models\Meeting;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\MeetingService;

trait CreatesSchoolData
{
    protected function makeUser(string $role, string $username, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($username),
            'email' => $username.'@escola.test',
            'username' => $username,
            'password' => 'senha123',
            'role' => $role,
            'active' => true,
        ], $attributes));
    }

    /** @return array{0: SchoolClass, 1: SchoolClass} */
    protected function makeClasses(?User $teacherA = null, ?User $teacherB = null): array
    {
        $year = SchoolYear::create(['name' => '5º Ano']);

        return [
            SchoolClass::create(['school_year_id' => $year->id, 'name' => '5º Ano A', 'teacher_id' => $teacherA?->id]),
            SchoolClass::create(['school_year_id' => $year->id, 'name' => '5º Ano B', 'teacher_id' => $teacherB?->id]),
        ];
    }

    protected function makeMeeting(array $classIds, array $overrides = []): Meeting
    {
        return app(MeetingService::class)->save(new Meeting, array_merge([
            'name' => 'Reunião de Pais - 2º Semestre',
            'date' => now()->addDays(10)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'duration_minutes' => 25,
            'break_minutes' => 5,
            'status' => 'published',
        ], $overrides), $classIds);
    }

    protected function slot(Meeting $meeting, SchoolClass $class, string $time): TimeSlot
    {
        return TimeSlot::where('meeting_id', $meeting->id)
            ->where('class_id', $class->id)
            ->where('start_time', $time.':00')
            ->firstOrFail();
    }
}
