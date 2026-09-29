@extends('layouts.staff')

@section('title', 'Histórico de alterações')

@section('content')
    <div class="page-header">
        <div>
            <h1>Histórico de alterações</h1>
            <p class="muted mb-0">O que foi alterado no sistema, por quem e quando. Mais recentes primeiro.</p>
        </div>
        <a href="{{ route('staff.reports.index') }}" class="btn btn-light">Voltar</a>
    </div>

    <nav class="tabs" aria-label="Filtrar por tipo">
        <a href="{{ route('staff.reports.logs', request()->except('entity', 'page')) }}" class="{{ request('entity') ? '' : 'active' }}">Tudo</a>
        @foreach($entities as $entity)
            <a href="{{ route('staff.reports.logs', array_merge(request()->except('page'), ['entity' => $entity])) }}"
               class="{{ request('entity') === $entity ? 'active' : '' }}">{{ \App\Models\AuditLog::ENTITIES[$entity] }}</a>
        @endforeach
    </nav>

    <form method="GET" class="filters" data-autosubmit>
        @if(request('entity'))<input type="hidden" name="entity" value="{{ request('entity') }}">@endif
        <div class="field">
            <label for="actor">Feito por</label>
            <select id="actor" name="actor">
                <option value="">Todos</option>
                @foreach($actors as $actor)
                    <option value="{{ $actor }}" @selected(request('actor') === $actor)>{{ $actor }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label class="check" style="margin-top:1.4rem"><input type="checkbox" name="logins" value="1" @checked(request()->boolean('logins')) onchange="this.form.submit()"> Mostrar acessos ao sistema</label>
        </div>
    </form>

    @if($logs->isEmpty())
        <div class="card empty">Nenhuma alteração registrada.</div>
    @else
        <ol class="history-list">
            @foreach($logs as $log)
                <li class="history-item">
                    <div class="history-head">
                        <span class="badge {{ $log->badgeClass() }}">{{ $log->heading() }}</span>
                        @if($log->title())<strong>{{ $log->title() }}</strong>@endif
                    </div>
                    <div class="muted small">{{ $log->created_at->format('d/m/Y \à\s H:i') }} · por {{ $log->actor ?? 'Sistema' }}</div>
                    @if($log->changes())
                        <ul class="history-changes">
                            @foreach($log->changes() as $c)
                                <li>
                                    <span class="history-field">{{ $c['campo'] }}:</span>
                                    @if($c['antes'] !== '')<del>{{ $c['antes'] }}</del> <span aria-hidden="true">→</span>@endif
                                    <span>{{ $c['depois'] !== '' ? $c['depois'] : '(vazio)' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ol>
        {{ $logs->links() }}
    @endif
@endsection
