<?php

namespace App\Queries;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Flat appointment listing shared by admin, coordinator, exports and teacher views. */
class AppointmentQuery
{
    public const FILTERS = ['meeting_id', 'date', 'school_year_id', 'class_id', 'teacher_id', 'time', 'status', 'q'];

    public static function filtersFrom(Request $request): array
    {
        $filters = [];
        foreach (self::FILTERS as $key) {
            $value = trim((string) $request->query($key, ''));
            if ($value !== '') {
                $filters[$key] = $value;
            }
        }
        $filters['status'] ??= 'confirmed';

        return $filters;
    }

    public static function build(array $filters, ?int $onlyTeacherId = null): Builder
    {
        $query = Appointment::query()
            ->leftJoin('time_slots as ts', 'ts.id', '=', 'appointments.time_slot_id')
            ->leftJoin('classes as c', 'c.id', '=', 'ts.class_id')
            ->leftJoin('school_years as y', 'y.id', '=', 'c.school_year_id')
            ->leftJoin('users as t', 't.id', '=', 'c.teacher_id')
            ->join('meetings as m', 'm.id', '=', 'appointments.meeting_id')
            ->select([
                'appointments.*',
                'ts.start_time', 'ts.end_time',
                'c.id as class_id', 'c.name as class_name',
                'y.id as school_year_id', 'y.name as school_year_name',
                't.id as teacher_id', 't.name as teacher_name',
                'm.name as meeting_name', 'm.date as meeting_date',
            ])
            ->orderBy('m.date')
            ->orderBy('ts.start_time')
            ->orderBy('c.name');

        if ($onlyTeacherId !== null) {
            $query->where('c.teacher_id', $onlyTeacherId);
        }

        foreach ($filters as $key => $value) {
            match ($key) {
                'meeting_id' => $query->where('appointments.meeting_id', (int) $value),
                'date' => $query->whereDate('m.date', $value),
                'school_year_id' => $query->where('y.id', (int) $value),
                'class_id' => $query->where('c.id', (int) $value),
                'teacher_id' => $query->where('c.teacher_id', (int) $value),
                'time' => $query->where('ts.start_time', substr($value, 0, 5).':00'),
                'status' => $value === 'all' ? null : $query->where('appointments.status', $value),
                'q' => $query->where(fn ($q) => $q
                    ->where('appointments.student_name', 'like', '%'.$value.'%')
                    ->orWhere('appointments.responsible_name', 'like', '%'.$value.'%')
                    ->orWhere('appointments.responsible_email', 'like', '%'.$value.'%')),
                default => null,
            };
        }

        return $query;
    }
}
