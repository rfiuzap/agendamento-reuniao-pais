@extends('layouts.public')

@section('title', 'Acesso da equipe')
@section('main-class', 'narrow')

@section('content')
    <div class="hero">
        <img src="{{ $brandLogoUrl }}" alt="{{ $schoolName }}" class="hero-logo">
        <h1>Acesso da equipe escolar</h1>
        <p class="muted">Administração, coordenação e professoras.</p>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('staff.login.store') }}" data-once>
            @csrf
            <div class="field">
                <label for="login">Usuário ou e-mail</label>
                <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                       class="@error('login') is-invalid @enderror">
            </div>
            <div class="field">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-block">Entrar</button>
        </form>
    </div>

    <p class="small mt-3" style="text-align:center"><a href="{{ route('parent.login') }}">Sou responsável por um aluno</a></p>
@endsection
