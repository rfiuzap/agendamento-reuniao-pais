<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Meeting;
use App\Models\SchoolClass;
use App\Models\TimeSlot;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Booking wizard. Progress is kept in the session ("booking" key) so that
 * personal data never travels in URLs.
 */
class BookingController extends Controller
{
    public function __construct(private AppointmentService $appointments)
    {
    }

    public function meetings(Request $request): View
    {
        $meetings = Meeting::bookable()->whereHas('classes')->orderBy('date')->orderBy('start_time')->get();
        $hasAppointments = Appointment::active()->where('responsible_email', $this->email($request))->exists();

        return view('parent.meetings', compact('meetings', 'hasAppointments'));
    }

    public function details(Request $request, Meeting $meeting): View|RedirectResponse
    {
        if (! $meeting->isBookable()) {
            return redirect()->route('parent.booking.meetings')->with('error', 'Esta reunião não está disponível.');
        }

        $classes = $this->meetingClasses($meeting);
        $years = $classes->pluck('schoolYear')->unique('id')->sortBy('name')->values();
        $draft = $this->draft($request, $meeting);

        $lastName = Appointment::where('responsible_email', $this->email($request))->latest('id')->value('responsible_name');

        return view('parent.details', [
            'meeting' => $meeting,
            'years' => $years,
            'classes' => $classes,
            'draft' => $draft + ['responsible_name' => $lastName],
        ]);
    }

    public function storeDetails(Request $request, Meeting $meeting): RedirectResponse
    {
        abort_unless($meeting->isBookable(), 404);
        $classIds = $this->meetingClasses($meeting)->pluck('id');

        $data = $request->validate([
            'school_year_id' => ['required', 'integer'],
            'class_id' => ['required', 'integer', Rule::in($classIds)],
            'responsible_name' => ['required', 'string', 'min:3', 'max:150'],
            'student_name' => ['required', 'string', 'min:3', 'max:150'],
        ], [], [
            'school_year_id' => 'sala/ano',
            'class_id' => 'turma',
            'responsible_name' => 'nome do responsável',
            'student_name' => 'nome do aluno',
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        if ($class->school_year_id !== (int) $data['school_year_id']) {
            return back()->withInput()->withErrors(['class_id' => 'A turma não pertence à sala/ano selecionada.']);
        }

        $request->session()->put('booking', [
            'meeting_id' => $meeting->id,
            'school_year_id' => $class->school_year_id,
            'class_id' => $class->id,
            'responsible_name' => AppointmentService::normalizeName($data['responsible_name']),
            'student_name' => AppointmentService::normalizeName($data['student_name']),
        ]);

        return redirect()->route('parent.booking.slots', $meeting);
    }

    public function slots(Request $request, Meeting $meeting): View|RedirectResponse
    {
        $draft = $this->draft($request, $meeting);
        if (! isset($draft['class_id'])) {
            return redirect()->route('parent.booking.details', $meeting);
        }

        $class = SchoolClass::with('teacher', 'schoolYear')->findOrFail($draft['class_id']);
        $slots = TimeSlot::where('meeting_id', $meeting->id)->where('class_id', $class->id)->orderBy('start_time')->get();

        return view('parent.slots', compact('meeting', 'class', 'slots', 'draft'));
    }

    public function selectSlot(Request $request, Meeting $meeting): RedirectResponse
    {
        $draft = $this->draft($request, $meeting);
        if (! isset($draft['class_id'])) {
            return redirect()->route('parent.booking.details', $meeting);
        }

        $data = $request->validate(['slot_id' => ['required', 'integer']]);
        $slot = TimeSlot::where('meeting_id', $meeting->id)->where('class_id', $draft['class_id'])->findOrFail($data['slot_id']);

        $this->appointments->precheck($this->email($request), $draft['student_name'], $slot);

        $request->session()->put('booking.slot_id', $slot->id);

        return redirect()->route('parent.booking.summary', $meeting);
    }

    public function summary(Request $request, Meeting $meeting): View|RedirectResponse
    {
        $draft = $this->draft($request, $meeting);
        if (! isset($draft['slot_id'])) {
            return redirect()->route('parent.booking.slots', $meeting);
        }

        $slot = TimeSlot::with('schoolClass.teacher', 'schoolClass.schoolYear')->findOrFail($draft['slot_id']);

        return view('parent.summary', compact('meeting', 'slot', 'draft'));
    }

    public function confirm(Request $request, Meeting $meeting): RedirectResponse
    {
        $draft = $this->draft($request, $meeting);
        if (! isset($draft['slot_id'])) {
            return redirect()->route('parent.booking.slots', $meeting);
        }

        try {
            $appointment = $this->appointments->book(
                $this->email($request), $draft['responsible_name'], $draft['student_name'], $draft['slot_id']
            );
        } catch (\App\Exceptions\BusinessRuleException $e) {
            $request->session()->forget('booking.slot_id');

            return redirect()->route('parent.booking.slots', $meeting)->with('error', $e->getMessage());
        }

        $request->session()->forget('booking');

        return redirect()->route('parent.appointments.success', $appointment);
    }

    private function email(Request $request): string
    {
        return $request->attributes->get('parent_email');
    }

    private function draft(Request $request, Meeting $meeting): array
    {
        $draft = $request->session()->get('booking', []);

        return ($draft['meeting_id'] ?? null) === $meeting->id ? $draft : [];
    }

    private function meetingClasses(Meeting $meeting)
    {
        return $meeting->classes()->where('classes.active', true)->with('schoolYear', 'teacher')->get();
    }
}
