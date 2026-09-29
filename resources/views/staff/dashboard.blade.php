@extends('layouts.staff')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <h1>Dashboard</h1>
        <a href="{{ route('staff.meetings.create') }}" class="btn">+ Nova reunião</a>
    </div>

    <div class="card mb-2">
        @include('staff._filters', ['fields' => ['meeting_id', 'date', 'school_year_id', 'class_id', 'teacher_id']])
    </div>

    <div class="stats mt-2">
        @foreach($stats as $label => $value)
            <div class="stat">
                <div class="stat-label">{{ $label }}</div>
                <div class="stat-value">{{ number_format($value, 0, ',', '.') }}</div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Ocupação por turma</h2>
            <a href="{{ route('staff.reports.index') }}" class="small">Ver relatórios</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Reunião</th><th>Turma</th><th>Professora</th><th>Reservados</th><th>Disponíveis</th><th>Ocupação</th></tr>
                </thead>
                <tbody>
                @forelse($occupancy as $row)
                    @php($pct = $row->total ? round($row->booked / $row->total * 100) : 0)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->meeting_date)->format('d/m/Y') }} · {{ $row->meeting_name }}</td>
                        <td class="nowrap">{{ $row->year_name }} {{ $row->class_name }}</td>
                        <td>{{ $row->teacher_name ?? '—' }}</td>
                        <td>{{ $row->booked }}</td>
                        <td>{{ $row->total - $row->booked }}</td>
                        <td style="min-width:140px">
                            <div class="progress" title="{{ $pct }}%"><span style="width: {{ $pct }}%"></span></div>
                            <span class="small muted">{{ $pct }}%</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Nenhum horário para os filtros selecionados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Últimos agendamentos</h2>
            <a href="{{ route('staff.appointments.index', $filters) }}" class="small">Ver todos</a>
        </div>
        @include('staff.appointments._table', ['rows' => $latest])
    </div>
@endsection
