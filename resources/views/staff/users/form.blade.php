@extends('layouts.staff')

@section('title', $user->exists ? 'Editar usuário' : 'Novo usuário')

@section('content')
    <div class="page-header">
        <h1>{{ $user->exists ? 'Editar usuário' : 'Novo usuário' }}</h1>
        <a href="{{ route('staff.users.index') }}" class="btn btn-light">Voltar</a>
    </div>

    <div class="card" style="max-width:760px">
        <form method="POST" action="{{ $user->exists ? route('staff.users.update', $user) : route('staff.users.store') }}">
            @csrf
            @if($user->exists) @method('PUT') @endif
            @include('staff.users._fields', ['roles' => \App\Models\User::ROLES])
            <p class="hint">Para vincular turmas a uma professora, use o menu <a href="{{ route('staff.teachers.index') }}">Professores</a>. Para desativar um usuário, desmarque "Ativo".</p>
            <button type="submit" class="btn mt-1">Salvar</button>
        </form>
    </div>
@endsection
