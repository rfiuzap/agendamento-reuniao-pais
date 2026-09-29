@extends('layouts.public')

@section('title', 'Alterar horário')
@section('header', true)

@section('content')
    <h1>Alterar horário</h1>
    <p class="muted">
        {{ $appointment->student_name }} · {{ $appointment->timeSlot->schoolClass->name }}<br>
        {{ $appointment->meeting->date->translatedFormat('l, d/m/Y') }} · Horário atual:
        <strong>{{ substr($appointment->timeSlot->start_time, 0, 5) }}</strong>
    </p>

    <div class="card">
        <p class="small muted">Toque em um horário disponível para trocar. O horário atual será liberado automaticamente.</p>
        @include('partials.slot-grid', [
            'slots' => $slots,
            'action' => route('parent.appointments.update', $appointment),
            'currentSlotId' => $appointment->time_slot_id,
        ])
    </div>

    <p class="mt-2"><a href="{{ route('parent.home') }}">‹ Voltar sem alterar</a></p>
@endsection
