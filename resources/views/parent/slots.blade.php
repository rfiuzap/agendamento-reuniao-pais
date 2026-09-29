@extends('layouts.public')

@section('title', 'Escolha o horário')
@section('header', true)

@section('content')
    @include('parent._steps', ['current' => 3])
    <h1>Escolha um horário</h1>
    <p class="muted">
        {{ $class->fullName() }}@if($class->teacher) · Prof.ª {{ $class->teacher->name }}@endif<br>
        {{ $meeting->date->translatedFormat('l, d/m/Y') }} · Aluno: <strong>{{ $draft['student_name'] }}</strong>
    </p>

    <div class="card">
        @include('partials.slot-grid', ['slots' => $slots, 'action' => route('parent.booking.slots.select', $meeting)])
    </div>

    <p class="mt-2"><a href="{{ route('parent.booking.details', $meeting) }}">‹ Alterar dados do aluno</a></p>
@endsection
