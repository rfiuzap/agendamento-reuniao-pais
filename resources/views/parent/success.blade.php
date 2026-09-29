@extends('layouts.public')

@section('title', 'Agendamento confirmado')
@section('header', true)

@section('content')
    <div class="card" style="text-align:center">
        <div style="font-size:2.5rem;line-height:1;color:var(--green)" aria-hidden="true">✓</div>
        <h1 class="mt-1">Agendamento confirmado!</h1>
        <p class="muted">Enviamos a confirmação para o seu e-mail.</p>
        <div style="text-align:left">
            <p class="mb-0 small muted">{{ $appointment->meeting->name }}</p>
            @include('parent._appointment-summary')
        </div>
        @include('parent._calendar-buttons')
        <a href="{{ route('parent.home') }}" class="btn mt-2">Ver meus agendamentos</a>
    </div>

    <div class="discreet">
        Tem mais de um filho? <a href="{{ route('parent.booking.meetings') }}">Agende a próxima reunião</a>.
    </div>
@endsection
