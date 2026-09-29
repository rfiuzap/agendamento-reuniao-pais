@extends('layouts.public')

@section('title', 'Cancelar agendamento')
@section('header', true)

@section('content')
    <div class="card">
        <h1>Deseja realmente cancelar este agendamento?</h1>
        <p class="muted">{{ $appointment->meeting->name }}</p>
        @include('parent._appointment-summary')

        <form method="POST" action="{{ route('parent.appointments.cancel.store', $appointment) }}" data-once>
            @csrf
            <div class="actions" style="justify-content:space-between">
                <a href="{{ route('parent.home') }}" class="btn btn-light">Não, manter</a>
                <button type="submit" class="btn btn-danger" data-loading="Cancelando...">Sim, cancelar agendamento</button>
            </div>
        </form>
    </div>
@endsection
