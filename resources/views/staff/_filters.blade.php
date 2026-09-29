{{-- Expects: $filters, $meetings, $years, $classes, $teachers; optional $fields (list of filters to show), $action, $extra (hidden inputs) --}}
@php($fields = $fields ?? ['meeting_id', 'date', 'school_year_id', 'class_id', 'teacher_id', 'time', 'status', 'q'])
<form method="GET" action="{{ $action ?? url()->current() }}" class="filters" data-autosubmit>
    @foreach(($extra ?? []) as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach

    @if(in_array('meeting_id', $fields))
        <div class="field">
            <label for="f-meeting">Reunião</label>
            <select id="f-meeting" name="meeting_id">
                <option value="">Todas</option>
                @foreach($meetings as $m)
                    <option value="{{ $m->id }}" @selected(($filters['meeting_id'] ?? '') == $m->id)>{{ $m->date->format('d/m/Y') }} · {{ $m->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if(in_array('date', $fields))
        <div class="field">
            <label for="f-date">Data</label>
            <input type="date" id="f-date" name="date" value="{{ $filters['date'] ?? '' }}" onchange="this.form.submit()">
        </div>
    @endif
    @if(in_array('school_year_id', $fields))
        <div class="field">
            <label for="f-year">Sala/Ano</label>
            <select id="f-year" name="school_year_id">
                <option value="">Todas</option>
                @foreach($years as $y)
                    <option value="{{ $y->id }}" @selected(($filters['school_year_id'] ?? '') == $y->id)>{{ $y->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if(in_array('class_id', $fields))
        <div class="field">
            <label for="f-class">Turma</label>
            <select id="f-class" name="class_id">
                <option value="">Todas</option>
                @foreach($classes as $c)
                    @continue(($filters['school_year_id'] ?? null) && $c->school_year_id != $filters['school_year_id'])
                    <option value="{{ $c->id }}" @selected(($filters['class_id'] ?? '') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if(in_array('teacher_id', $fields))
        <div class="field">
            <label for="f-teacher">Professora</label>
            <select id="f-teacher" name="teacher_id">
                <option value="">Todas</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->id }}" @selected(($filters['teacher_id'] ?? '') == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if(in_array('time', $fields))
        <div class="field">
            <label for="f-time">Horário</label>
            <input type="time" id="f-time" name="time" value="{{ $filters['time'] ?? '' }}" onchange="this.form.submit()">
        </div>
    @endif
    @if(in_array('status', $fields))
        <div class="field">
            <label for="f-status">Status</label>
            <select id="f-status" name="status">
                <option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>Confirmados</option>
                <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelados</option>
                <option value="all" @selected(($filters['status'] ?? '') === 'all')>Todos</option>
            </select>
        </div>
    @endif
    @if(in_array('q', $fields))
        <div class="field">
            <label for="f-q">Buscar</label>
            <input type="text" id="f-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Aluno, responsável, e-mail">
        </div>
    @endif
    <div class="field actions">
        <button type="submit" class="btn btn-sm">Filtrar</button>
        <a href="{{ ($action ?? url()->current()).(!empty($extra) ? '?'.http_build_query($extra) : '') }}" class="btn btn-light btn-sm">Limpar</a>
    </div>
</form>
