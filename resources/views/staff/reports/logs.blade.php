@extends('layouts.staff')

@section('title', 'Logs de alterações')

@php
    $actions = ['created' => 'Criou', 'updated' => 'Alterou', 'deleted' => 'Excluiu', 'cancelled' => 'Cancelou',
        'rescheduled' => 'Remarcou', 'exported' => 'Exportou', 'login' => 'Entrou'];
    $entities = collect($entities);
@endphp

@section('content')
    <div class="page-header">
        <h1>Logs de alterações</h1>
        <a href="{{ route('staff.reports.index') }}" class="btn btn-light">Voltar</a>
    </div>

    <nav class="tabs">
        <a href="{{ route('staff.reports.logs') }}" class="{{ request('entity') ? '' : 'active' }}">Todos</a>
        @foreach($entities as $entity)
            <a href="{{ route('staff.reports.logs', ['entity' => $entity]) }}" class="{{ request('entity') === $entity ? 'active' : '' }}">{{ $entity }}</a>
        @endforeach
    </nav>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Registro</th><th>Detalhes</th><th>IP</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->user?->name ?? $log->actor ?? '—' }}</td>
                    <td><span class="badge badge-blue">{{ $actions[$log->action] ?? $log->action }}</span></td>
                    <td class="nowrap">{{ $log->entity }}{{ $log->entity_id ? ' #'.$log->entity_id : '' }}</td>
                    <td class="small" style="max-width:380px;word-break:break-word">{{ $log->data ? json_encode($log->data, JSON_UNESCAPED_UNICODE) : '' }}</td>
                    <td class="small muted">{{ $log->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Nenhum registro.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
@endsection
