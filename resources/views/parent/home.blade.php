@extends('layouts.public')

@section('title', 'Meus agendamentos')
@section('header', true)

@section('content')
    <h1>{{ $appointments->count() > 1 ? 'Você já possui agendamentos.' : 'Você já possui um agendamento.' }}</h1>
    <p class="muted">Confira os detalhes abaixo. Se precisar, altere o horário ou cancele.</p>

    @foreach($appointments as $appointment)
        <div class="card appointment-card">
            <div class="card-header">
                <h3>{{ $appointment->meeting->name }}</h3>
                <span class="badge badge-green">{{ $appointment->statusLabel() }}</span>
            </div>
            @include('parent._appointment-summary')
            @if($appointment->meeting->isBookable())
                <div class="actions">
                    <a href="{{ route('parent.appointments.edit', $appointment) }}" class="btn btn-light">ALTERAR</a>
                    <a href="{{ route('parent.appointments.cancel', $appointment) }}" class="btn btn-outline-danger">CANCELAR</a>
                    <a href="{{ route('parent.appointments.calendar', $appointment) }}" class="btn-link small" style="margin-left:auto">+ Agenda do celular</a>
                </div>
            @else
                <p class="small muted mb-0">O prazo para alterações desta reunião foi encerrado.</p>
            @endif
        </div>
    @endforeach

    <div class="mt-3" style="text-align:center">
        <a href="{{ route('parent.booking.meetings') }}" class="btn">+ Adicionar outro filho</a>
    </div>
@endsection
