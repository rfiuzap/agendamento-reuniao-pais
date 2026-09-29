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
        <div class="stats mt-2">
            <div class="stat"><div class="stat-label">Horários</div><div class="stat-value">{{ $total }}</div></div>
            <div class="stat"><div class="stat-label">Reservados</div><div class="stat-value">{{ $booked }}</div></div>
            <div class="stat"><div class="stat-label">Disponíveis</div><div class="stat-value">{{ $total - $booked }}</div></div>
            <div class="stat"><div class="stat-label">Ocupação</div><div class="stat-value">{{ $total ? round($booked / $total * 100) : 0 }}%</div></div>
            <div class="stat"><div class="stat-label">Cancelamentos</div><div class="stat-value">{{ $cancelled }}</div></div>
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
            <h2>Procura por horário</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Horário</th><th>Reservados</th><th>Disponíveis</th><th>Ocupação</th></tr></thead>
                    <tbody>
                    @foreach($byTime as $row)
                        @php($pct = $row->total ? round($row->booked / $row->total * 100) : 0)
                        <tr>
                            <td><strong>{{ substr($row->start_time, 0, 5) }}</strong></td>
                            <td>{{ $row->booked }}</td>
                            <td>{{ $row->total - $row->booked }}</td>
                            <td style="min-width:140px"><div class="progress"><span style="width: {{ $pct }}%"></span></div><span class="small muted">{{ $pct }}%</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
