@extends('layouts.staff')

@section('title', 'Professores')

@section('content')
    <div class="page-header">
        <h1>Professores</h1>
        <a href="{{ route('staff.teachers.create') }}" class="btn">+ Nova professora</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Nome</th><th>Usuário</th><th>E-mail</th><th>Turmas</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
            <tbody>
            @forelse($teachers as $teacher)
                <tr>
                    <td><strong>{{ $teacher->name }}</strong></td>
                    <td>{{ $teacher->username }}</td>
                    <td>{{ $teacher->email }}</td>
                    <td>{{ $teacher->classes->pluck('name')->join(', ') ?: '—' }}</td>
                    <td><span class="badge {{ $teacher->active ? 'badge-green' : '' }}">{{ $teacher->active ? 'Ativa' : 'Inativa' }}</span></td>
                    <td class="text-right"><a href="{{ route('staff.teachers.edit', $teacher) }}" class="btn btn-light btn-sm">Editar</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Nenhuma professora cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
