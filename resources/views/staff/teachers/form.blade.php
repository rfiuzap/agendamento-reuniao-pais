@extends('layouts.staff')

@section('title', $teacher->exists ? 'Editar professora' : 'Nova professora')

@section('content')
    @php
        $selected = collect(old('class_ids', $selected))->map(fn ($v) => (int) $v)->all();
    @endphp
    <div class="page-header">
        <h1>{{ $teacher->exists ? 'Editar professora' : 'Nova professora' }}</h1>
        <a href="{{ route('staff.teachers.index') }}" class="btn btn-light">Voltar</a>
    </div>

    <form method="POST" action="{{ $teacher->exists ? route('staff.teachers.update', $teacher) : route('staff.teachers.store') }}">
        @csrf
        @if($teacher->exists) @method('PUT') @endif

        <div class="card">
            @include('staff.users._fields', ['user' => $teacher])
        </div>

        <div class="card">
            <h2>Turmas vinculadas</h2>
            <p class="muted small">Cada turma possui uma professora. Ao vincular uma turma que já possui outra professora, ela será transferida.</p>
            @php
                $period = fn ($class) => match (true) {
                    str_contains(mb_strtolower($class->name), 'manhã') => 'Manhã',
                    str_contains(mb_strtolower($class->name), 'tarde') => 'Tarde',
                    default => 'Outras',
                };
                $columns = collect(['Manhã' => collect(), 'Tarde' => collect(), 'Outras' => collect()])
                    ->merge($classes->groupBy($period))
                    ->map(fn ($list) => $list->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE));
            @endphp
            <div class="class-columns">
                @foreach($columns as $title => $list)
                    <div>
                        <h3 class="class-column-title">{{ $title }}</h3>
                        @forelse($list as $class)
                            <label class="check">
                                <input type="checkbox" name="class_ids[]" value="{{ $class->id }}" @checked(in_array($class->id, $selected))>
                                <span>{{ $class->fullName() }}
                                    @if($class->teacher && $class->teacher_id !== $teacher->id)
                                        <span class="small muted">({{ $class->teacher->name }})</span>
                                    @endif
                                </span>
                            </label>
                        @empty
                            <p class="small muted mb-0">Nenhuma turma.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn mt-2">Salvar</button>
    </form>
@endsection
