<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ $schoolName }}</title>
    <link rel="icon" href="{{ $logoUrl }}">
    <link rel="manifest" href="{{ route('manifest') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=8">
    <script src="{{ asset('js/app.js') }}?v=4" defer></script>
</head>
<body>
@php
    $user = auth()->user();
    $is = fn (string ...$patterns) => request()->routeIs(...$patterns) ? 'active' : '';
    $group = request('group');
@endphp
<div class="staff">
    <aside class="sidebar" aria-label="Menu principal">
        <a class="brand" href="{{ route($user->homeRoute()) }}">
            <img src="{{ $logoUrl }}" alt="" width="34" height="34">
            <span>{{ $schoolName }}</span>
        </a>

        <nav class="nav">
            @if($user->isAdmin())
                <div class="nav-group">
                    <a href="{{ route('staff.dashboard') }}" class="{{ $is('staff.dashboard') }}">Dashboard</a>
                </div>
            @endif

            @if($user->hasRole('admin', 'coordinator'))
                <div class="nav-group">
                    <div class="nav-title">Reuniões</div>
                    <a href="{{ route('staff.meetings.index') }}" class="{{ $is('staff.meetings.index', 'staff.meetings.show', 'staff.meetings.edit') }}">Listar reuniões</a>
                    @if($user->isAdmin())
                        <a href="{{ route('staff.meetings.create') }}" class="{{ $is('staff.meetings.create') }}">Nova reunião</a>
                    @endif
                </div>

                <div class="nav-group">
                    <div class="nav-title">Escola</div>
                    <a href="{{ route('staff.years.index') }}" class="{{ $is('staff.years.*') }}">Salas/Anos</a>
                    <a href="{{ route('staff.classes.index') }}" class="{{ $is('staff.classes.*') }}">Turmas</a>
                    @if($user->isAdmin())
                        <a href="{{ route('staff.teachers.index') }}" class="{{ $is('staff.teachers.*') }}">Professores</a>
                    @endif
                </div>

                <div class="nav-group">
                    <div class="nav-title">Agendamentos</div>
                    @foreach(\App\Http\Controllers\Staff\AppointmentController::GROUPS as $key => $label)
                        <a href="{{ route('staff.appointments.index', $key ? ['group' => $key] : []) }}"
                           class="{{ request()->routeIs('staff.appointments.index') && (string) $group === $key ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            @endif

            @if($user->isAdmin())
                <div class="nav-group">
                    <div class="nav-title">Administração</div>
                    <a href="{{ route('staff.users.index') }}" class="{{ $is('staff.users.*') }}">Usuários</a>
                    <a href="{{ route('staff.reports.index') }}" class="{{ $is('staff.reports.*') }}">Relatórios</a>
                    <a href="{{ route('staff.settings.edit') }}" class="{{ $is('staff.settings.*') }}">Configurações</a>
                </div>
            @endif

            @if($user->hasRole('teacher'))
                <div class="nav-group">
                    <a href="{{ route('staff.teacher.dashboard') }}" class="{{ $is('staff.teacher.dashboard') }}">Minhas turmas</a>
                </div>
            @endif
        </nav>
        <div class="app-version sidebar-version">{{ \App\Support\AppVersion::label() }}</div>
    </aside>

    <div class="staff-main">
        <header class="topbar">
            <button type="button" class="menu-toggle" data-menu-toggle aria-label="Abrir menu">☰</button>
            <div class="muted small">@yield('title')</div>
            <div class="user">
                <span><strong>{{ $user->name }}</strong> <span class="role muted">· {{ $user->roleLabel() }}</span></span>
                <a href="{{ route('staff.password.edit') }}" class="small">Alterar senha</a>
                <form method="POST" action="{{ route('staff.logout') }}" class="mb-0">
                    @csrf
                    <button type="submit" class="btn btn-light btn-sm">Sair</button>
                </form>
            </div>
        </header>

        <main class="content">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
