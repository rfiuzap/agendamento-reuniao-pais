@extends('layouts.public')

@section('title', 'Escolha a reunião')
@section('header', true)

@section('content')
    @include('parent._steps', ['current' => 1])
    <h1>Escolha a reunião</h1>

    @if($meetings->isEmpty())
        <div class="card" style="text-align:center">
            <h2>Nenhuma reunião liberada no momento</h2>
            <p class="muted mb-0">Ainda não há reuniões de pais abertas para agendamento. Assim que a escola liberar uma nova reunião, você poderá agendar por aqui.</p>
        </div>
    @else
        <p class="muted">{{ \App\Models\Setting::get('parent_instructions') }}</p>
        <div class="option-list">
            @foreach($meetings as $meeting)
                <a class="option" href="{{ route('parent.booking.details', $meeting) }}">
                    <div>
                        <div class="option-title">{{ $meeting->name }}</div>
                        <div class="muted small">
                            {{ $meeting->date->translatedFormat('l, d/m/Y') }} · {{ $meeting->timeRange() }}
                        </div>
                    </div>
                    <span class="option-arrow" aria-hidden="true">›</span>
                </a>
            @endforeach
        </div>
    @endif

    @if($hasAppointments)
        <p class="mt-3"><a href="{{ route('parent.home') }}">‹ Voltar para meus agendamentos</a></p>
    @endif
@endsection
