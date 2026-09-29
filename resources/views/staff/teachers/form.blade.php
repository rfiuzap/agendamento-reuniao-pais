@extends('layouts.staff')

@section('title', $teacher->exists ? 'Editar professora' : 'Nova professora')

@section('content')
    @php($selected = collect(old('class_ids', $selected))->map(fn ($v) => (int) $v)->all())
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
            <div class="class-checks" style="padding-left:0">
                @foreach($classes as $class)
                    <label class="check">
                        <input type="checkbox" name="class_ids[]" value="{{ $class->id }}" @checked(in_array($class->id, $selected))>
                        <span>{{ $class->name }}
                            @if($class->teacher && $class->teacher_id !== $teacher->id)
                                <span class="small muted">({{ $class->teacher->name }})</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn mt-2">Salvar</button>
    </form>
@endsection
