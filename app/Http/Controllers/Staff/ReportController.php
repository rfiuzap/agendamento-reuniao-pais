<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Meeting;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $meetings = Meeting::orderByDesc('date')->get(['id', 'name', 'date']);
        $meetingId = (int) $request->query('meeting_id', $meetings->first()?->id);
        $meeting = $meetings->firstWhere('id', $meetingId);

        $byClass = collect();
        $byTime = collect();
        if ($meeting) {
            $base = TimeSlot::where('time_slots.meeting_id', $meeting->id);
            $booked = "sum(case when time_slots.status = 'booked' then 1 else 0 end)";

            $byClass = (clone $base)
                ->join('classes as c', 'c.id', '=', 'time_slots.class_id')
                ->join('school_years as y', 'y.id', '=', 'c.school_year_id')
                ->leftJoin('users as t', 't.id', '=', 'c.teacher_id')
                ->groupBy('c.id', 'c.name', 'y.name', 't.name')
                ->orderBy('c.name')
                ->selectRaw("c.name as class_name, y.name as year_name, t.name as teacher_name, count(*) as total, {$booked} as booked")
                ->get();

            $byTime = (clone $base)
                ->join('classes as c', 'c.id', '=', 'time_slots.class_id')
                ->with('activeAppointment')
                ->orderBy('time_slots.start_time')
                ->orderBy('c.name')
                ->select('time_slots.*', 'c.name as class_name')
                ->get();
        }

        $cancelled = $meeting ? $meeting->appointments()->where('status', 'cancelled')->count() : 0;

        return view('staff.reports.index', compact('meetings', 'meeting', 'byClass', 'byTime', 'cancelled'));
    }

    public function logs(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->when($request->query('entity'), fn ($q, $e) => $q->where('entity', $e))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $entities = AuditLog::distinct()->orderBy('entity')->pluck('entity');

        return view('staff.reports.logs', compact('logs', 'entities'));
    }
}
