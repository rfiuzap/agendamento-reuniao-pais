<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\TimeSlot;
use App\Models\User;
use App\Queries\AppointmentQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $filters = array_intersect_key(
            AppointmentQuery::filtersFrom($request),
            array_flip(['meeting_id', 'date', 'school_year_id', 'class_id', 'teacher_id'])
        );

        $appointments = AppointmentQuery::build($filters + ['status' => 'confirmed']);

        $slots = TimeSlot::query()
            ->join('classes as c', 'c.id', '=', 'time_slots.class_id')
            ->join('meetings as m', 'm.id', '=', 'time_slots.meeting_id')
            ->when($filters['meeting_id'] ?? null, fn ($q, $v) => $q->where('time_slots.meeting_id', $v))
            ->when($filters['date'] ?? null, fn ($q, $v) => $q->whereDate('m.date', $v))
            ->when($filters['school_year_id'] ?? null, fn ($q, $v) => $q->where('c.school_year_id', $v))
            ->when($filters['class_id'] ?? null, fn ($q, $v) => $q->where('c.id', $v))
            ->when($filters['teacher_id'] ?? null, fn ($q, $v) => $q->where('c.teacher_id', $v));

        $meetingsQuery = Meeting::query()
            ->when($filters['meeting_id'] ?? null, fn ($q, $v) => $q->whereKey($v))
            ->when($filters['date'] ?? null, fn ($q, $v) => $q->whereDate('date', $v));

        $stats = [
            'Reuniões' => (clone $meetingsQuery)->count(),
            'Salas/Anos' => SchoolYear::where('active', true)->count(),
            'Turmas' => SchoolClass::where('active', true)->count(),
            'Reservas' => (clone $appointments)->count(),
            'Horários disponíveis' => (clone $slots)->where('time_slots.status', 'available')->count(),
            'Responsáveis' => (clone $appointments)->distinct()->count('appointments.responsible_email'),
            'Alunos' => (clone $appointments)->distinct()->count('appointments.student_id'),
        ];

        $occupancy = (clone $slots)
            ->leftJoin('users as t', 't.id', '=', 'c.teacher_id')
            ->join('school_years as y', 'y.id', '=', 'c.school_year_id')
            ->groupBy('m.id', 'm.name', 'm.date', 'c.id', 'c.name', 'y.name', 't.name')
            ->orderBy('m.date')->orderBy('y.name')->orderBy('c.name')
            ->selectRaw("m.name as meeting_name, m.date as meeting_date, c.name as class_name, y.name as year_name, t.name as teacher_name,
                count(*) as total, sum(case when time_slots.status = 'booked' then 1 else 0 end) as booked")
            ->limit(100)
            ->get();

        $latest = (clone $appointments)->reorder('appointments.created_at', 'desc')->limit(10)->get();

        return view('staff.dashboard', [
            'stats' => $stats,
            'filters' => $filters,
            'occupancy' => $occupancy,
            'latest' => $latest,
            'meetings' => Meeting::orderByDesc('date')->get(['id', 'name', 'date']),
            'years' => SchoolYear::orderBy('name')->get(['id', 'name']),
            'classes' => SchoolClass::orderBy('name')->get(['id', 'name', 'school_year_id']),
            'teachers' => User::teachers()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
