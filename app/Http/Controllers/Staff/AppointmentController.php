<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Meeting;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\TimeSlot;
use App\Models\User;
use App\Queries\AppointmentQuery;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public const GROUPS = [
        '' => 'Todos',
        'meeting' => 'Por reunião',
        'year' => 'Por sala',
        'class' => 'Por turma',
    ];

    public function __construct(private AppointmentService $appointments)
    {
    }

    public function index(Request $request): View
    {
        $filters = AppointmentQuery::filtersFrom($request);
        $group = array_key_exists($request->query('group', ''), self::GROUPS) ? $request->query('group', '') : '';

        $query = AppointmentQuery::build($filters);
        $total = (clone $query)->count();

        if ($group) {
            $groupKey = ['meeting' => 'meeting_name', 'year' => 'school_year_name', 'class' => 'class_name'][$group];
            $items = $query->limit(2000)->get();
            $grouped = $items->groupBy(fn ($a) => $group === 'class'
                ? trim($a->school_year_name.' '.$a->class_name) ?: 'Sem turma' // same class name exists in several rooms
                : ($a->{$groupKey} ?? 'Sem turma'));
            $appointments = null;
        } else {
            $appointments = $query->paginate(50)->withQueryString();
            $grouped = null;
        }

        return view('staff.appointments.index', array_merge($this->filterOptions(), [
            'filters' => $filters,
            'group' => $group,
            'appointments' => $appointments,
            'grouped' => $grouped,
            'total' => $total,
        ]));
    }

    public function edit(Appointment $appointment): View|RedirectResponse
    {
        if (! $appointment->isActive()) {
            return redirect()->route('staff.appointments.index')->with('error', 'Agendamentos cancelados não podem ser alterados.');
        }

        $appointment->load(['meeting', 'timeSlot.schoolClass.teacher']);
        $slots = TimeSlot::where('meeting_id', $appointment->meeting_id)
            ->with('schoolClass')
            ->join('classes', 'classes.id', '=', 'time_slots.class_id')
            ->orderBy('classes.name')->orderBy('time_slots.start_time')
            ->select('time_slots.*')
            ->get();

        return view('staff.appointments.edit', compact('appointment', 'slots'));
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'slot_id' => ['required', 'integer', Rule::exists('time_slots', 'id')->where('meeting_id', $appointment->meeting_id)],
        ]);

        $this->appointments->reschedule($appointment, (int) $data['slot_id'], sameClassOnly: false);

        return redirect()->route('staff.appointments.index')->with('success', 'Agendamento alterado e responsável notificado por e-mail.');
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        $this->appointments->cancel($appointment);

        return back()->with('success', 'Agendamento cancelado e horário liberado.');
    }

    private function filterOptions(): array
    {
        return [
            'meetings' => Meeting::orderByDesc('date')->get(['id', 'name', 'date']),
            'years' => SchoolYear::orderBy('name')->get(['id', 'name']),
            'classes' => SchoolClass::orderBy('name')->get(['id', 'name', 'school_year_id']),
            'teachers' => User::teachers()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
