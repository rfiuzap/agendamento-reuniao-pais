@extends('layouts.staff')

@section('title', 'Minhas turmas')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $teacher->name }}</h1>
            <p class="muted mb-0">Turmas: {{ $classes->pluck('name')->join(', ') ?: 'nenhuma turma vinculada' }}</p>
        </div>
    </div>

    @if($meetings->isEmpty())
        <div class="card empty">Nenhuma reunião cadastrada para suas turmas.</div>
    @else
        <div class="card mb-2">
            <form method="GET" class="filters" data-autosubmit>
                <div class="field">
                    <label for="meeting_id">Reunião</label>
                    <select id="meeting_id" name="meeting_id">
                        @foreach($meetings as $m)
                            <option value="{{ $m->id }}" @selected($meeting?->id === $m->id)>{{ $m->date->format('d/m/Y') }} · {{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($classes->count() > 1)
                    <div class="field">
                        <label for="class_id">Turma</label>
                        <select id="class_id" name="class_id">
                            <option value="">Todas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" @selected($classFilter == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>
        </div>

        <div class="stats mt-2">
            @foreach($stats as $label => $value)
                <div class="stat"><div class="stat-label">{{ $label }}</div><div class="stat-value">{{ $value }}</div></div>
            @endforeach
        </div>

        @forelse($rows as $classId => $slots)
            <div class="card">
                <div class="card-header">
                    <h2>{{ $slots->first()->schoolClass->name }}</h2>
                    <span class="muted">Reunião: {{ $meeting->date->format('d/m/Y') }}</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Horário</th><th>Responsável</th><th>Aluno</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($slots as $slot)
                            @php($a = $slot->activeAppointment)
                            <tr>
                                <td class="nowrap"><strong>{{ $slot->label() }}</strong> <span class="muted small">às {{ substr($slot->end_time, 0, 5) }}</span></td>
                                <td>{{ $a?->responsible_name ?? '—' }}@if($a)<div class="small muted">{{ $a->responsible_email }}</div>@endif</td>
                                <td>{{ $a?->student_name ?? '—' }}</td>
                                <td>
                                    @if($a)
                                        <span class="badge badge-green">{{ $a->statusLabel() }}</span>
                                    @else
                                        <span class="badge">Disponível</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card empty">Suas turmas não participam desta reunião.</div>
        @endforelse
    @endif
@endsection
