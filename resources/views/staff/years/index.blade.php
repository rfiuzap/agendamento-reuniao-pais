@extends('layouts.staff')

@section('title', 'Salas/Anos')

@section('content')
    @php($admin = auth()->user()->isAdmin())
    <div class="page-header">
        <h1>Salas/Anos</h1>
        @if($admin)<a href="{{ route('staff.years.create') }}" class="btn">+ Nova sala/ano</a>@endif
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Nome</th><th>Turmas</th><th>Status</th>@if($admin)<th class="text-right">Ações</th>@endif</tr></thead>
            <tbody>
            @forelse($years as $year)
                <tr>
                    <td><strong>{{ $year->name }}</strong></td>
                    <td><a href="{{ route('staff.classes.index', ['school_year_id' => $year->id]) }}">{{ $year->classes_count }} turma(s)</a></td>
                    <td><span class="badge {{ $year->active ? 'badge-green' : '' }}">{{ $year->active ? 'Ativa' : 'Inativa' }}</span></td>
                    @if($admin)
                        <td class="text-right">
                            <div class="actions" style="justify-content:flex-end">
                                <a href="{{ route('staff.classes.create', ['school_year_id' => $year->id]) }}" class="btn btn-light btn-sm">+ Turma</a>
                                <a href="{{ route('staff.years.edit', $year) }}" class="btn btn-light btn-sm">Editar</a>
                                @if($year->classes_count === 0)
                                    <form method="POST" action="{{ route('staff.years.destroy', $year) }}" data-confirm="Excluir {{ $year->name }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm">Excluir</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="4" class="empty">Nenhuma sala/ano cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
