@extends('layouts.staff')

@section('title', 'Usuários')

@section('content')
    <div class="page-header">
        <h1>Usuários</h1>
        <a href="{{ route('staff.users.create') }}" class="btn">+ Novo usuário</a>
    </div>

    <nav class="tabs">
        <a href="{{ route('staff.users.index') }}" class="{{ request('role') ? '' : 'active' }}">Todos</a>
        @foreach(\App\Models\User::ROLES as $key => $label)
            <a href="{{ route('staff.users.index', ['role' => $key]) }}" class="{{ request('role') === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Nome</th><th>Usuário</th><th>E-mail</th><th>Perfil</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td><strong>{{ $user->name }}</strong></td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->email }}</td>
                    <td><span class="badge badge-blue">{{ $user->roleLabel() }}</span></td>
                    <td><span class="badge {{ $user->active ? 'badge-green' : '' }}">{{ $user->active ? 'Ativo' : 'Inativo' }}</span></td>
                    <td class="text-right">
                        <a href="{{ $user->role === 'teacher' ? route('staff.teachers.edit', $user) : route('staff.users.edit', $user) }}" class="btn btn-light btn-sm">Editar</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
