@extends('layouts.staff')

@section('title', $meeting->name)

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $meeting->name }}</h1>
            <p class="muted mb-0">
                {{ $meeting->date->translatedFormat('l, d/m/Y') }} · {{ $meeting->timeRange() }} ·
                {{ $meeting->duration_minutes }} min por atendimento + {{ $meeting->break_minutes }} min de intervalo ·
                <span class="badge {{ ['draft' => '', 'published' => 'badge-green', 'closed' => 'badge-amber'][$meeting->status] }}">{{ $meeting->statusLabel() }}</span>
            </p>
        </div>
        <div class="actions">
            <a href="{{ route('staff.appointments.index', ['meeting_id' => $meeting->id]) }}" class="btn btn-light">Ver agendamentos</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('staff.meetings.edit', $meeting) }}" class="btn">Editar</a>
            @endif
        </div>
    </div>

    @if($meeting->status === 'draft')
        <div class="alert alert-warning">Esta reunião está em <strong>rascunho</strong> e não aparece para os responsáveis. Edite e altere o status para "Liberada" quando estiver pronta.</div>
    @endif

    @forelse($meeting->classes as $class)
        @php($classSlots = $slots->get($class->id, collect()))
        <div class="card">
            <div class="card-header">
                <h3>{{ $class->name }} <span class="muted small">· {{ $class->schoolYear->name }} · {{ $class->teacher?->name ?? 'Sem professora' }}</span></h3>
                <span class="badge badge-blue">{{ $classSlots->where('status', 'booked')->count() }} / {{ $classSlots->count() }} reservados</span>
            </div>
            <div class="slot-grid">
                @foreach($classSlots as $slot)
                    @if($slot->isAvailable())
                        <div class="slot slot-available" style="cursor:default">
                            <span class="slot-time">{{ $slot->label() }}</span>
                            <span class="slot-status">Disponível</span>
                        </div>
                    @else
                        <div class="slot slot-booked" style="cursor:default" title="{{ $slot->activeAppointment?->responsible_name }}">
                            <span class="slot-time">{{ $slot->label() }}</span>
                            <span class="slot-status">{{ \Illuminate\Support\Str::limit($slot->activeAppointment?->student_name ?? 'Reservado', 18) }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @empty
        <div class="card empty">Nenhuma turma vinculada.</div>
    @endforelse
@endsection
