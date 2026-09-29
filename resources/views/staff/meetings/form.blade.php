@extends('layouts.staff')

@section('title', $meeting->exists ? 'Editar reunião' : 'Nova reunião')

@section('content')
    @php($selected = collect(old('class_ids', $selected))->map(fn ($v) => (int) $v)->all())
    <div class="page-header">
        <h1>{{ $meeting->exists ? 'Editar reunião' : 'Nova reunião' }}</h1>
        <a href="{{ $meeting->exists ? route('staff.meetings.show', $meeting) : route('staff.meetings.index') }}" class="btn btn-light">Voltar</a>
    </div>

    <form method="POST" action="{{ $meeting->exists ? route('staff.meetings.update', $meeting) : route('staff.meetings.store') }}" data-once>
        @csrf
        @if($meeting->exists) @method('PUT') @endif

        <div class="card">
            <h2>Dados da reunião</h2>
            <div class="field">
                <label for="name">Nome da reunião</label>
                <input type="text" id="name" name="name" required maxlength="150" placeholder="Reunião de Pais - 2º Semestre"
                       value="{{ old('name', $meeting->name) }}" class="@error('name') is-invalid @enderror">
            </div>
            <div class="grid-3">
                <div class="field">
                    <label for="date">Data</label>
                    <input type="date" id="date" name="date" required value="{{ old('date', $meeting->date?->format('Y-m-d')) }}" class="@error('date') is-invalid @enderror">
                </div>
                <div class="field">
                    <label for="start_time">Horário inicial</label>
                    <input type="time" id="start_time" name="start_time" required value="{{ old('start_time', substr($meeting->start_time, 0, 5)) }}" class="@error('start_time') is-invalid @enderror">
                </div>
                <div class="field">
                    <label for="end_time">Horário final</label>
                    <input type="time" id="end_time" name="end_time" required value="{{ old('end_time', substr($meeting->end_time, 0, 5)) }}" class="@error('end_time') is-invalid @enderror">
                </div>
                <div class="field">
                    <label for="duration_minutes">Duração do atendimento (min)</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" required min="5" max="240" value="{{ old('duration_minutes', $meeting->duration_minutes) }}" class="@error('duration_minutes') is-invalid @enderror">
                </div>
                <div class="field">
                    <label for="break_minutes">Intervalo entre atendimentos (min)</label>
                    <input type="number" id="break_minutes" name="break_minutes" required min="0" max="120" value="{{ old('break_minutes', $meeting->break_minutes) }}" class="@error('break_minutes') is-invalid @enderror">
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        @foreach(\App\Models\Meeting::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $meeting->status) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="hint mb-0">Os horários são gerados automaticamente: cada atendimento dura a duração informada e o próximo começa após o intervalo. Somente reuniões "Liberadas" aparecem para os responsáveis.</p>
        </div>

        <div class="card">
            <h2>Salas/Anos e turmas participantes</h2>
            <p class="muted small">Ao marcar uma sala/ano, todas as suas turmas são selecionadas. Desmarque turmas individualmente se necessário.</p>
            @error('class_ids')<div class="alert alert-error">{{ $message }}</div>@enderror

            @forelse($years as $year)
                <div class="year-block">
                    <label class="check">
                        <input type="checkbox" data-year-toggle>
                        <strong>{{ $year->name }}</strong>
                        @unless($year->active)<span class="badge">Inativa</span>@endunless
                    </label>
                    <div class="class-checks">
                        @forelse($year->classes as $class)
                            <label class="check">
                                <input type="checkbox" name="class_ids[]" value="{{ $class->id }}" data-class-box @checked(in_array($class->id, $selected))>
                                {{ $class->name }}
                                @unless($class->active)<span class="badge">Inativa</span>@endunless
                            </label>
                        @empty
                            <span class="muted small">Nenhuma turma cadastrada.</span>
                        @endforelse
                    </div>
                </div>
            @empty
                <p class="muted">Cadastre salas/anos e turmas antes de criar reuniões. <a href="{{ route('staff.years.create') }}">Cadastrar sala/ano</a></p>
            @endforelse
        </div>

        <div class="actions mt-2" style="justify-content:space-between">
            <div>
                @if($meeting->exists)
                    <button type="submit" form="delete-form" class="btn btn-outline-danger">Excluir reunião</button>
                @endif
            </div>
            <button type="submit" class="btn" data-loading="Salvando...">Salvar e gerar horários</button>
        </div>
    </form>

    @if($meeting->exists)
        <form id="delete-form" method="POST" action="{{ route('staff.meetings.destroy', $meeting) }}" data-confirm="Excluir esta reunião? Esta ação não pode ser desfeita.">
            @csrf @method('DELETE')
        </form>
    @endif
@endsection
