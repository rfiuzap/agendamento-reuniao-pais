@extends('layouts.staff')

@section('title', 'Alterar agendamento')

@section('content')
    <div class="page-header">
        <h1>Alterar agendamento</h1>
        <a href="{{ route('staff.appointments.index') }}" class="btn btn-light">Voltar</a>
    </div>

    <div class="card">
        <dl class="summary">
            <dt>Reunião</dt><dd>{{ $appointment->meeting->name }} · {{ $appointment->meeting->date->format('d/m/Y') }}</dd>
            <dt>Responsável</dt><dd>{{ $appointment->responsible_name }} ({{ $appointment->responsible_email }})</dd>
            <dt>Aluno</dt><dd>{{ $appointment->student_name }}</dd>
            <dt>Horário atual</dt><dd>{{ $appointment->timeSlot?->schoolClass?->name }} · {{ $appointment->timeSlot?->label() ?? '—' }}</dd>
        </dl>
        <p class="muted small">Escolha o novo horário. O responsável receberá um e-mail com a alteração.</p>

        @foreach($slots->groupBy('class_id') as $classSlots)
            <h3 class="mt-3">{{ $classSlots->first()->schoolClass->name }}</h3>
            @include('partials.slot-grid', [
                'slots' => $classSlots,
                'action' => route('staff.appointments.update', $appointment),
                'method' => 'PUT',
                'currentSlotId' => $appointment->time_slot_id,
            ])
        @endforeach
    </div>
@endsection
