<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\SchoolYear;
use App\Models\TimeSlot;
use App\Services\MeetingService;
use App\Services\SlotGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function __construct(private MeetingService $meetings)
    {
    }

    public function index(Request $request): View
    {
        $meetings = Meeting::withCount([
            'classes',
            'timeSlots',
            'timeSlots as booked_count' => fn ($q) => $q->where('status', 'booked'),
        ])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        return view('staff.meetings.index', compact('meetings'));
    }

    public function show(Request $request, Meeting $meeting): View
    {
        $meeting->load('classes.teacher', 'classes.schoolYear');
        $slots = TimeSlot::where('meeting_id', $meeting->id)
            ->with('activeAppointment')
            ->orderBy('start_time')
            ->get()
            ->groupBy('class_id');

        return view('staff.meetings.show', compact('meeting', 'slots'));
    }

    public function create(): View
    {
        return view('staff.meetings.form', [
            'meeting' => new Meeting(['status' => 'draft', 'duration_minutes' => 25, 'break_minutes' => 5, 'start_time' => '08:00', 'end_time' => '12:00']),
            'years' => $this->years(),
            'selected' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $classIds] = $this->validated($request);
        $meeting = $this->meetings->save(new Meeting, $data, $classIds);

        return redirect()->route('staff.meetings.show', $meeting)->with('success', 'Reunião criada e horários gerados.');
    }

    public function edit(Meeting $meeting): View
    {
        return view('staff.meetings.form', [
            'meeting' => $meeting,
            'years' => $this->years(),
            'selected' => $meeting->classes()->pluck('classes.id')->all(),
        ]);
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        [$data, $classIds] = $this->validated($request);
        $this->meetings->save($meeting, $data, $classIds);

        return redirect()->route('staff.meetings.show', $meeting)->with('success', 'Reunião atualizada.');
    }

    public function destroy(Meeting $meeting): RedirectResponse
    {
        $this->meetings->delete($meeting);

        return redirect()->route('staff.meetings.index')->with('success', 'Reunião excluída.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'status' => ['required', Rule::in(array_keys(Meeting::STATUSES))],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['integer', Rule::exists('classes', 'id')],
        ], [
            'class_ids.required' => 'Selecione ao menos uma turma participante.',
            'end_time.after' => 'O horário final deve ser posterior ao inicial.',
        ], [
            'name' => 'nome', 'date' => 'data', 'start_time' => 'horário inicial', 'end_time' => 'horário final',
            'duration_minutes' => 'duração', 'break_minutes' => 'intervalo',
        ]);

        if (! SlotGenerator::generate($data['start_time'], $data['end_time'], $data['duration_minutes'], $data['break_minutes'])) {
            back()->withInput()->withErrors(['duration_minutes' => 'A duração não cabe no período informado.'])->throwResponse();
        }

        $classIds = $data['class_ids'];
        unset($data['class_ids']);

        return [$data, $classIds];
    }

    private function years()
    {
        return SchoolYear::with(['classes' => fn ($q) => $q->with('teacher')])->orderBy('name')->get();
    }
}
