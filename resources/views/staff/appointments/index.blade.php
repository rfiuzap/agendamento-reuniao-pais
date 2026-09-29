@extends('layouts.staff')

@section('title', 'Agendamentos')

@section('content')
    <div class="page-header">
        <div>
            <h1>Agendamentos</h1>
            <p class="muted mb-0">{{ $total }} {{ $total === 1 ? 'registro encontrado' : 'registros encontrados' }}</p>
        </div>
    </div>

    <nav class="tabs" aria-label="Agrupamento">
        @foreach(\App\Http\Controllers\Staff\AppointmentController::GROUPS as $key => $label)
            <a href="{{ route('staff.appointments.index', array_filter(array_merge($filters, ['group' => $key]))) }}"
               class="{{ $group === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="card">
        @include('staff._filters', ['extra' => $group ? ['group' => $group] : []])

        @if($grouped !== null)
            @forelse($grouped as $title => $rows)
                <h3 class="mt-3">{{ $title }} <span class="badge badge-blue">{{ $rows->count() }}</span></h3>
                @include('staff.appointments._table', ['rows' => $rows])
            @empty
                <p class="empty">Nenhum agendamento encontrado.</p>
            @endforelse
        @else
            @include('staff.appointments._table', ['rows' => $appointments])
            {{ $appointments->links() }}
        @endif
    </div>
@endsection
