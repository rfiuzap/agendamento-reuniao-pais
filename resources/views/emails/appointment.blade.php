@extends('emails.layout')

@php
    $slot = $appointment->timeSlot;
    $class = $slot?->schoolClass;
@endphp

@section('content')
    <p style="margin:0 0 12px;">Olá, {{ $appointment->responsible_name }}.</p>

    @if($type === 'confirmed')
        <p style="margin:0 0 20px;">Seu agendamento para a reunião de pais foi <strong style="color:#15803d;">confirmado</strong>.</p>
    @elseif($type === 'updated')
        <p style="margin:0 0 20px;">Seu agendamento foi <strong style="color:#1d4ed8;">alterado</strong>. Confira o novo horário:</p>
    @else
        <p style="margin:0 0 20px;">Seu agendamento foi <strong style="color:#b91c1c;">cancelado</strong> e o horário foi liberado.</p>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:10px;font-size:14px;">
        <tr><td style="padding:10px 14px;color:#64748b;width:40%;">Reunião</td><td style="padding:10px 14px;font-weight:bold;">{{ $appointment->meeting->name }}</td></tr>
        <tr><td style="padding:10px 14px;color:#64748b;border-top:1px solid #e2e8f0;">Aluno</td><td style="padding:10px 14px;border-top:1px solid #e2e8f0;">{{ $appointment->student_name }}</td></tr>
        <tr><td style="padding:10px 14px;color:#64748b;border-top:1px solid #e2e8f0;">Turma</td><td style="padding:10px 14px;border-top:1px solid #e2e8f0;">{{ $class?->name ?? '—' }}</td></tr>
        <tr><td style="padding:10px 14px;color:#64748b;border-top:1px solid #e2e8f0;">Data</td><td style="padding:10px 14px;border-top:1px solid #e2e8f0;">{{ $appointment->meeting->date->format('d/m/Y') }}</td></tr>
        <tr><td style="padding:10px 14px;color:#64748b;border-top:1px solid #e2e8f0;">Horário</td><td style="padding:10px 14px;border-top:1px solid #e2e8f0;font-weight:bold;">{{ $slot ? substr($slot->start_time, 0, 5).' às '.substr($slot->end_time, 0, 5) : '—' }}</td></tr>
        <tr><td style="padding:10px 14px;color:#64748b;border-top:1px solid #e2e8f0;">Professora</td><td style="padding:10px 14px;border-top:1px solid #e2e8f0;">{{ $class?->teacher?->name ?? '—' }}</td></tr>
    </table>

    @if($type !== 'cancelled')
        <p style="margin:20px 0 0;">
            <strong>Salve na sua agenda:</strong> abra o convite anexo (.ics) no celular
            @if($googleUrl) ou <a href="{{ $googleUrl }}" style="color:#1d4ed8;">adicione ao Google Agenda</a>@endif.
        </p>
        <p style="margin:12px 0 0;">Para alterar ou cancelar, acesse <a href="{{ route('parent.login') }}" style="color:#1d4ed8;">{{ route('parent.login') }}</a> com este e-mail.</p>
    @endif
@endsection
