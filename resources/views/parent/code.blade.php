@extends('layouts.public', ['hideErrorSummary' => true])

@section('title', 'Código de acesso')
@section('main-class', 'narrow')

@section('content')
    <div class="hero">
        <img src="{{ $logoUrl }}" alt="">
        <h1>Digite o código</h1>
        <p class="muted">Enviamos um código de 6 dígitos para<br><strong>{{ $email }}</strong></p>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('parent.code.verify') }}" data-once>
            @csrf
            <div class="field">
                <label for="code" class="sr-only">Código</label>
                <input type="text" id="code" name="code" required autofocus maxlength="6" pattern="\d{6}"
                       inputmode="numeric" autocomplete="one-time-code" placeholder="000000"
                       class="code-input @error('code') is-invalid @enderror">
                @error('code')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-block" data-loading="Verificando...">Entrar</button>
        </form>

        <p class="hint mt-2">O código vale por {{ config('reuniao.code_ttl_minutes') }} minutos. Verifique também a caixa de spam.</p>

        <div class="actions" style="justify-content:space-between">
            <form method="POST" action="{{ route('parent.code.resend') }}">
                @csrf
                <button type="submit" class="btn-link">Enviar novo código</button>
            </form>
            <a href="{{ route('parent.login') }}" class="small">Usar outro e-mail</a>
        </div>
    </div>
@endsection
