@extends('layouts.staff')

@section('title', 'Reuniões')

@section('content')
    <div class="page-header">
        <h1>Reuniões</h1>
        @if(auth()->user()->isAdmin())
            <a href="{{ route('staff.meetings.create') }}" class="btn">+ Nova reunião</a>
        @endif
    </div>

    <nav class="tabs">
        <a href="{{ route('staff.meetings.index') }}" class="{{ request('status') ? '' : 'active' }}">Todas</a>
        @foreach(\App\Models\Meeting::STATUSES as $key => $label)
            <a href="{{ route('staff.meetings.index', ['status' => $key]) }}" class="{{ request('status') === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Reunião</th><th>Data</th><th>Horário</th><th>Atendimento</th><th>Turmas</th><th>Reservas</th><th>Status</th><th class="text-right">Ações</th></tr>
            </thead>
            <tbody>
            @forelse($meetings as $meeting)
                <tr>
                    <td><a href="{{ route('staff.meetings.show', $meeting) }}"><strong>{{ $meeting->name }}</strong></a></td>
                    <td class="nowrap">{{ $meeting->date->format('d/m/Y') }}</td>
                    <td class="nowrap">{{ $meeting->timeRange() }}</td>
                    <td class="nowrap">{{ $meeting->duration_minutes }} min + {{ $meeting->break_minutes }} min</td>
                    <td>{{ $meeting->classes_count }}</td>
                    <td class="nowrap">{{ $meeting->booked_count }} / {{ $meeting->time_slots_count }}</td>
                    <td>
                        <span class="badge {{ ['draft' => '', 'published' => 'badge-green', 'closed' => 'badge-amber'][$meeting->status] }}">{{ $meeting->statusLabel() }}</span>
                    </td>
                    <td class="text-right">
                        <div class="actions" style="justify-content:flex-end">
                            <a href="{{ route('staff.meetings.show', $meeting) }}" class="btn btn-light btn-sm">Ver</a>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('staff.meetings.edit', $meeting) }}" class="btn btn-light btn-sm">Editar</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">Nenhuma reunião cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $meetings->links() }}
@endsection
