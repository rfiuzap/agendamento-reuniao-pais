<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Agendamento de Reunião de Pais') · {{ $schoolName }}</title>
    <link rel="icon" href="{{ asset('img/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=4">
    <script src="{{ asset('js/app.js') }}?v=2" defer></script>
</head>
<body>
@php($parentEmail = session(\App\Http\Middleware\EnsureParentAuthenticated::SESSION_EMAIL))
@hasSection('header')
    <header class="public-header">
        <div class="inner">
            <a class="brand" href="{{ route('parent.home') }}">
                <img src="{{ asset('img/logo.svg') }}" alt="">
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
    <div class="app-version">{{ \App\Support\AppVersion::label() }}</div>
</footer>
</body>
</html>
