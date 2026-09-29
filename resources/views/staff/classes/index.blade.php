@extends('layouts.staff')

@section('title', 'Turmas')

@section('content')
    @php($admin = auth()->user()->isAdmin())
    <div class="page-header">
        <h1>Turmas</h1>
        @if($admin)<a href="{{ route('staff.classes.create', request()->only('school_year_id')) }}" class="btn">+ Nova turma</a>@endif
    </div>

    <nav class="tabs">
        <a href="{{ route('staff.classes.index') }}" class="{{ request('school_year_id') ? '' : 'active' }}">Todas</a>
        @foreach($years as $year)
            <a href="{{ route('staff.classes.index', ['school_year_id' => $year->id]) }}" class="{{ request('school_year_id') == $year->id ? 'active' : '' }}">{{ $year->name }}</a>
        @endforeach
    </nav>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Turma</th><th>Sala/Ano</th><th>Professora</th><th>Status</th>@if($admin)<th class="text-right">Ações</th>@endif</tr></thead>
            <tbody>
            @forelse($classes as $class)
                <tr>
                    <td><strong>{{ $class->name }}</strong></td>
                    <td>{{ $class->schoolYear->name }}</td>
                    <td>{{ $class->teacher?->name ?? '—' }}</td>
                    <td><span class="badge {{ $class->active ? 'badge-green' : '' }}">{{ $class->active ? 'Ativa' : 'Inativa' }}</span></td>
                    @if($admin)
                        <td class="text-right">
                            <div class="actions" style="justify-content:flex-end">
                                <a href="{{ route('staff.classes.edit', $class) }}" class="btn btn-light btn-sm">Editar</a>
                                <form method="POST" action="{{ route('staff.classes.destroy', $class) }}" data-confirm="Excluir a turma {{ $class->name }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm">Excluir</button>
                                </form>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Nenhuma turma cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
