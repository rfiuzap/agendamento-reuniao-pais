<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Services\AppointmentService;
use App\Services\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __construct(private AppointmentService $appointments)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $appointments = Appointment::active()
            ->where('responsible_email', $this->email($request))
            ->whereHas('meeting', fn ($q) => $q->whereDate('date', '>=', today()))
            ->with(['meeting', 'timeSlot.schoolClass.teacher'])
            ->get()
            ->sortBy(fn ($a) => $a->meeting->date->format('Y-m-d').$a->timeSlot?->start_time);

        if ($appointments->isEmpty()) {
            return redirect()->route('parent.booking.meetings');
        }

        return view('parent.home', compact('appointments'));
    }

    public function success(Request $request, Appointment $appointment): View
    {
        $this->authorizeOwner($request, $appointment);
        $appointment->load(['meeting', 'timeSlot.schoolClass.teacher']);

        return view('parent.success', compact('appointment'));
    }

    public function calendar(Request $request, Appointment $appointment): Response
    {
        $this->authorizeOwner($request, $appointment);
        abort_unless($appointment->isActive() && $appointment->timeSlot, 404);

        $event = new CalendarEvent($appointment);

        return response($event->ics(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="'.$event->filename().'"',
        ]);
    }

    public function edit(Request $request, Appointment $appointment): View|RedirectResponse
    {
        $this->authorizeOwner($request, $appointment);
        if (! $appointment->isActive() || ! $appointment->meeting->isBookable()) {
            return redirect()->route('parent.home')->with('error', 'Este agendamento não pode mais ser alterado.');
        }

        $appointment->load(['meeting', 'timeSlot.schoolClass.teacher']);
        $slots = TimeSlot::where('meeting_id', $appointment->meeting_id)
            ->where('class_id', $appointment->timeSlot->class_id)
            ->orderBy('start_time')
            ->get();

        return view('parent.edit', compact('appointment', 'slots'));
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeOwner($request, $appointment);
        $data = $request->validate(['slot_id' => ['required', 'integer']]);

        $this->appointments->reschedule($appointment, (int) $data['slot_id']);

        return redirect()->route('parent.home')->with('success', 'Agendamento alterado com sucesso. Enviamos a confirmação por e-mail.');
    }

    public function confirmCancel(Request $request, Appointment $appointment): View|RedirectResponse
    {
        $this->authorizeOwner($request, $appointment);
        if (! $appointment->isActive()) {
            return redirect()->route('parent.home');
        }
        $appointment->load(['meeting', 'timeSlot.schoolClass.teacher']);

        return view('parent.cancel', compact('appointment'));
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeOwner($request, $appointment);
        $this->appointments->cancel($appointment);

        return redirect()->route('parent.home')->with('success', 'Agendamento cancelado. O horário foi liberado e enviamos a confirmação por e-mail.');
    }

    private function email(Request $request): string
    {
        return $request->attributes->get('parent_email');
    }

    private function authorizeOwner(Request $request, Appointment $appointment): void
    {
        abort_unless($appointment->responsible_email === $this->email($request), 404);
    }
}
