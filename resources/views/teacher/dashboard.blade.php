@extends('layouts.staff')

@section('title', 'Minhas turmas')

@section('content')
    <div class="page-header no-print">
        <div>
            <h1>{{ $teacher->name }}</h1>
            <p class="muted mb-0">Turmas: {{ $classes->map->fullName()->join(', ') ?: 'nenhuma turma vinculada' }}</p>
        </div>
        @if($rows->isNotEmpty())
            <button type="button" class="btn" onclick="window.print()">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Imprimir
            </button>
        @endif
    </div>

    @if($meetings->isEmpty())
        <div class="card empty">Nenhuma reunião cadastrada para suas turmas.</div>
    @else
        <div class="card mb-2 no-print">
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
                                <option value="{{ $c->id }}" @selected($classFilter == $c->id)>{{ $c->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>
        </div>

        <div class="stats mt-2 no-print">
            @foreach($stats as $label => $value)
                <div class="stat"><div class="stat-label">{{ $label }}</div><div class="stat-value">{{ $value }}</div></div>
            @endforeach
        </div>

        @forelse($rows as $classId => $slots)
            <div class="card class-card">
                <div class="card-header">
                    <div>
                        <h2>{{ $slots->first()->schoolClass->fullName() }}</h2>
                        <div class="muted small">Prof.ª {{ $teacher->name }}</div>
                    </div>
                    <span class="muted">{{ $meeting->name }} · {{ $meeting->date->format('d/m/Y') }}</span>
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
