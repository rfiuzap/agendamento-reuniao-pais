@extends('layouts.staff')

@section('title', 'Relatórios')

@section('content')
    <div class="page-header">
        <h1>Relatórios</h1>
        <a href="{{ route('staff.reports.logs') }}" class="btn btn-light">Logs de alterações</a>
    </div>

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
        </form>
    </div>

    @if(! $meeting)
        <div class="card empty">Nenhuma reunião cadastrada.</div>
    @else
        @php($total = $byClass->sum('total'))
        @php($booked = $byClass->sum('booked'))
        @php($pct = fn ($part, $whole) => $whole ? round($part / $whole * 100) : 0)
        <div class="stats mt-2">
            <div class="stat"><div class="stat-label">Horários</div><div class="stat-value">{{ $total }}</div></div>
            <div class="stat"><div class="stat-label">Reservados</div><div class="stat-value">{{ $booked }} <span class="stat-pct">{{ $pct($booked, $total) }}%</span></div></div>
            <div class="stat"><div class="stat-label">Disponíveis</div><div class="stat-value">{{ $total - $booked }} <span class="stat-pct">{{ $pct($total - $booked, $total) }}%</span></div></div>
            <div class="stat" title="Percentual sobre todos os agendamentos feitos (ativos + cancelados)">
                <div class="stat-label">Cancelamentos</div>
                <div class="stat-value">{{ $cancelled }} <span class="stat-pct">{{ $pct($cancelled, $booked + $cancelled) }}%</span></div>
            </div>
        </div>

        <div class="card">
            <h2>Ocupação por turma</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Sala/Ano</th><th>Turma</th><th>Professora</th><th>Reservados</th><th>Disponíveis</th><th>Ocupação</th></tr></thead>
                    <tbody>
                    @foreach($byClass as $row)
                        @php($pct = $row->total ? round($row->booked / $row->total * 100) : 0)
                        <tr>
                            <td>{{ $row->year_name }}</td>
                            <td><strong>{{ $row->class_name }}</strong></td>
                            <td>{{ $row->teacher_name ?? '—' }}</td>
                            <td>{{ $row->booked }}</td>
                            <td>{{ $row->total - $row->booked }}</td>
                            <td style="min-width:140px"><div class="progress"><span style="width: {{ $pct }}%"></span></div><span class="small muted">{{ $pct }}%</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Horários</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Horário</th><th>Sala</th><th>Turma</th><th>Responsável / Aluno</th><th>Situação</th></tr></thead>
                    <tbody>
                    @foreach($byTime as $slot)
                        @php($a = $slot->activeAppointment)
                        <tr>
                            <td><strong>{{ substr($slot->start_time, 0, 5) }}</strong></td>
                            <td class="nowrap">{{ $slot->year_name }}</td>
                            <td class="nowrap">{{ $slot->class_name }}</td>
                            <td>
                                @if($a)
                                    {{ $a->responsible_name }}
                                    <div class="small muted">{{ $a->student_name }}</div>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($a)
                                    <span class="badge badge-red">Reservado</span>
                                @else
                                    <span class="badge badge-green">Livre</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
