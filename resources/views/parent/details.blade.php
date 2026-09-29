@extends('layouts.public', ['hideErrorSummary' => true])

@section('title', 'Dados do aluno')
@section('header', true)

@section('content')
    @include('parent._steps', ['current' => 2])
    <h1>{{ $meeting->name }}</h1>
    <p class="muted">{{ $meeting->date->translatedFormat('l, d/m/Y') }} · {{ $meeting->timeRange() }}</p>

    <div class="card">
        <form method="POST" action="{{ route('parent.booking.details.store', $meeting) }}" data-once>
            @csrf
            <div class="grid-2">
                <div class="field">
                    <label for="school_year_id">Sala/Ano do seu filho</label>
                    <select id="school_year_id" name="school_year_id" required data-year-select="class_id"
                            class="@error('school_year_id') is-invalid @enderror">
                        <option value="">Selecione</option>
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" @selected(old('school_year_id', $draft['school_year_id'] ?? null) == $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                    @error('school_year_id')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="class_id">Turma</label>
                    <select id="class_id" name="class_id" required class="@error('class_id') is-invalid @enderror">
                        <option value="">Selecione a turma</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" data-year="{{ $class->school_year_id }}"
                                @selected(old('class_id', $draft['class_id'] ?? null) == $class->id)>
                                {{ $class->name }}@if($class->teacher) — Prof.ª {{ $class->teacher->name }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('class_id')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="field">
                <label for="responsible_name">Seu nome (responsável)</label>
                <input type="text" id="responsible_name" name="responsible_name" required maxlength="150" autocomplete="name"
                       value="{{ old('responsible_name', $draft['responsible_name'] ?? '') }}"
                       class="@error('responsible_name') is-invalid @enderror">
                @error('responsible_name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="student_name">Nome do aluno</label>
                <input type="text" id="student_name" name="student_name" required maxlength="150" autocomplete="off"
                       value="{{ old('student_name', $draft['student_name'] ?? '') }}"
                       class="@error('student_name') is-invalid @enderror">
                @error('student_name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="actions" style="justify-content:space-between">
                <a href="{{ route('parent.booking.meetings') }}" class="btn btn-light">Voltar</a>
                <button type="submit" class="btn">Ver horários disponíveis</button>
            </div>
        </form>
    </div>
@endsection
