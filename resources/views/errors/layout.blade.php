<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="public-main narrow" style="text-align:center">
    <img src="{{ $logoUrl }}" alt="" width="64" height="64">
    <h1 class="mt-2">@yield('heading')</h1>
    <p class="muted">@yield('message')</p>
    <a href="{{ url('/') }}" class="btn">Ir para o início</a>
</main>
</body>
</html>
