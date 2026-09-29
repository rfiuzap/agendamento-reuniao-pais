@extends('layouts.staff')

@section('title', $class->exists ? 'Editar turma' : 'Nova turma')

@section('content')
    <div class="page-header">
        <h1>{{ $class->exists ? 'Editar turma' : 'Nova turma' }}</h1>
        <a href="{{ route('staff.classes.index') }}" class="btn btn-light">Voltar</a>
    </div>

    <div class="card" style="max-width:640px">
        <form method="POST" action="{{ $class->exists ? route('staff.classes.update', $class) : route('staff.classes.store') }}">
            @csrf
            @if($class->exists) @method('PUT') @endif
            <div class="field">
                <label for="school_year_id">Sala/Ano</label>
                <select id="school_year_id" name="school_year_id" required class="@error('school_year_id') is-invalid @enderror">
                    <option value="">Selecione</option>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}" @selected(old('school_year_id', $class->school_year_id) == $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="name">Nome da turma</label>
                <input type="text" id="name" name="name" required maxlength="80" placeholder="Ex.: 5º Ano A"
                       value="{{ old('name', $class->name) }}" class="@error('name') is-invalid @enderror">
            </div>
            <div class="field">
                <label for="teacher_id">Professora</label>
                <select id="teacher_id" name="teacher_id">
                    <option value="">Sem professora</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected(old('teacher_id', $class->teacher_id) == $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="check"><input type="checkbox" name="active" value="1" @checked(old('active', $class->active))> Ativa</label>
                <div class="hint">Turmas inativas não aparecem para os responsáveis.</div>
            </div>
            <button type="submit" class="btn">Salvar</button>
        </form>
    </div>
@endsection
