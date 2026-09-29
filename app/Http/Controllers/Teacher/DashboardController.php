<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** RB11: a teacher only sees slots and appointments of her own classes. */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user();
        $classes = $teacher->classes()->with('schoolYear')->get();
        $classIds = $classes->pluck('id');

        $meetings = Meeting::whereHas('classes', fn ($q) => $q->whereIn('classes.id', $classIds))
            ->where('status', '!=', 'draft')
            ->orderByDesc('date')
            ->get();

        $default = $meetings->filter(fn ($m) => $m->date->gte(today()))->sortBy('date')->first() ?? $meetings->first();
        $meeting = $meetings->firstWhere('id', (int) $request->query('meeting_id', $default?->id));

        $classFilter = $request->query('class_id');
        $visibleClasses = $classFilter ? $classes->where('id', (int) $classFilter) : $classes;

        $rows = collect();
        if ($meeting) {
            $rows = TimeSlot::where('meeting_id', $meeting->id)
                ->whereIn('class_id', $visibleClasses->pluck('id'))
                ->with(['activeAppointment', 'schoolClass.schoolYear'])
                ->orderBy('start_time')
                ->get()
                ->groupBy('class_id');
        }

        $all = $rows->flatten();
        $stats = [
            'Total de alunos' => $all->filter(fn ($s) => $s->activeAppointment)->pluck('activeAppointment.student_id')->unique()->count(),
            'Total agendado' => $all->where('status', 'booked')->count(),
            'Total disponível' => $all->where('status', 'available')->count(),
        ];

        return view('teacher.dashboard', compact('teacher', 'classes', 'meetings', 'meeting', 'rows', 'stats', 'classFilter'));
    }
}
