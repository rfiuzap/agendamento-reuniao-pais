@php($event = new \App\Services\CalendarEvent($appointment))
<div class="calendar-buttons">
    <a href="{{ route('parent.appointments.calendar', $appointment) }}" class="btn btn-light">
        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M12 13v5M9.5 15.5h5"/></svg>
        Adicionar à agenda do celular
    </a>
    <a href="{{ $event->googleUrl() }}" target="_blank" rel="noopener" class="btn-link small">Usa Google Agenda? Adicionar por aqui</a>
</div>
