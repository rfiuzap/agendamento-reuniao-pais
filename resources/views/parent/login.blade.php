@extends('layouts.public')

@section('main-class', 'narrow')

@section('content')
    <div class="hero">
        <img src="{{ $brandLogoUrl }}" alt="{{ $schoolName }}" class="hero-logo">
        <h1>Agendamento de Reunião de Pais</h1>
        <p class="muted">Para agendar a reunião com a professora, informe seu e-mail. Não precisa de senha.</p>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('parent.code.send') }}" data-once>
            @csrf
            <div class="field">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       autocomplete="email" inputmode="email" placeholder="seuemail@exemplo.com"
                       class="@error('email') is-invalid @enderror">
            </div>
            <button type="submit" class="btn btn-block" data-loading="Enviando...">Continuar</button>
        </form>
        <p class="hint mt-2 mb-0">Enviaremos um código de 6 dígitos para esse e-mail.</p>
    </div>

    <p class="staff-link">
        <a href="{{ route('staff.login') }}">Login da equipe escolar</a>
    </p>
@endsection
