@php($slot = $appointment->timeSlot)
<dl class="summary">
    <dt>Aluno</dt><dd>{{ $appointment->student_name }}</dd>
    <dt>Turma</dt><dd>{{ $slot?->schoolClass?->name ?? '—' }}</dd>
    <dt>Data</dt><dd>{{ $appointment->meeting->date->format('d/m/Y') }}</dd>
    <dt>Horário</dt><dd>{{ $slot ? substr($slot->start_time, 0, 5).' às '.substr($slot->end_time, 0, 5) : '—' }}</dd>
    <dt>Professora</dt><dd>{{ $slot?->schoolClass?->teacher?->name ?? '—' }}</dd>
</dl>
