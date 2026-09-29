<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Agendamento de Reunião de Pais') · {{ $schoolName }}</title>
    <link rel="icon" href="{{ $logoUrl }}">
    <link rel="apple-touch-icon" href="{{ $logoUrl }}">
    <link rel="manifest" href="{{ route('manifest') }}">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Reunião de Pais">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=5">
    <script src="{{ asset('js/app.js') }}?v=3" defer data-sw="{{ asset('sw.js') }}"></script>
</head>
<body>
@php($parentEmail = session(\App\Http\Middleware\EnsureParentAuthenticated::SESSION_EMAIL))
@hasSection('header')
    <header class="public-header">
        <div class="inner">
            <a class="brand" href="{{ route('parent.home') }}">
                <img src="{{ $logoUrl }}" alt="">
                <span>{{ $schoolName }}</span>
            </a>
            @if($parentEmail)
                <form method="POST" action="{{ route('parent.logout') }}" class="mb-0">
                    @csrf
                    <span class="muted small" style="margin-right:.5rem">{{ $parentEmail }}</span>
                    <button type="submit" class="btn btn-light btn-sm">Sair</button>
                </form>
            @endif
        </div>
    </header>
@endif

<main class="public-main @yield('main-class')">
    @include('partials.flash')
    @yield('content')
</main>

<footer class="public-footer">
    @if($contactEmail || $contactPhone)<div>Dúvidas: {{ $contactEmail }} {{ $contactPhone }}</div>@endif
    <button type="button" class="install-app" data-install-app hidden>
        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M12 7v7m-3-3 3 3 3-3M10 18h4"/></svg>
        Salvar no celular
    </button>
    <p class="install-help" data-install-help hidden>
        No iPhone: toque em <strong>Compartilhar</strong> <span aria-hidden="true">⎋</span> e depois em <strong>Adicionar à Tela de Início</strong>.
    </p>
    <div class="app-version">{{ \App\Support\AppVersion::label() }}</div>
</footer>
</body>
</html>
