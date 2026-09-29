@extends('layouts.staff')

@section('title', 'Alterar senha')

@section('content')
    <div class="page-header"><h1>{{ $firstAccess ? 'Crie sua senha' : 'Alterar senha' }}</h1></div>

    <div class="card" style="max-width:520px">
        @if($firstAccess)
            <div class="alert alert-info">Este é o seu primeiro acesso. Para continuar, escolha uma senha pessoal.</div>
        @endif
        <form method="POST" action="{{ route('staff.password.update') }}" data-once>
            @csrf @method('PUT')
            <div class="field">
                <label for="password">Nova senha</label>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" autofocus
                       class="@error('password') is-invalid @enderror">
                <div class="hint">Mínimo de 8 caracteres.</div>
            </div>
            <div class="field">
                <label for="password_confirmation">Repita a nova senha</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn">Salvar senha</button>
        </form>
    </div>
@endsection
