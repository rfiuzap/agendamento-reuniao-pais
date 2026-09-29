@extends('layouts.public')

@section('title', 'Confirme o agendamento')
@section('header', true)

@section('content')
    @include('parent._steps', ['current' => 4])
    <h1>Confira e confirme</h1>

    <div class="card">
        <dl class="summary">
            <dt>Reunião</dt><dd>{{ $meeting->name }}</dd>
            <dt>Data</dt><dd>{{ $meeting->date->translatedFormat('l, d/m/Y') }}</dd>
            <dt>Horário</dt><dd>{{ substr($slot->start_time, 0, 5) }} às {{ substr($slot->end_time, 0, 5) }}</dd>
            <dt>Sala/Ano</dt><dd>{{ $slot->schoolClass->schoolYear->name }}</dd>
            <dt>Turma</dt><dd>{{ $slot->schoolClass->name }}</dd>
            <dt>Professora</dt><dd>{{ $slot->schoolClass->teacher?->name ?? '—' }}</dd>
            <dt>Responsável</dt><dd>{{ $draft['responsible_name'] }}</dd>
            <dt>Aluno</dt><dd>{{ $draft['student_name'] }}</dd>
        </dl>

        <form method="POST" action="{{ route('parent.booking.confirm', $meeting) }}" data-once>
            @csrf
            <div class="actions" style="justify-content:space-between">
                <a href="{{ route('parent.booking.slots', $meeting) }}" class="btn btn-light">Trocar horário</a>
                <button type="submit" class="btn" data-loading="Confirmando...">Confirmar agendamento</button>
            </div>
        </form>
    </div>
@endsection
