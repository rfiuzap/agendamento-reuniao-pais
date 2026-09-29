@extends('layouts.staff')

@section('title', $year->exists ? 'Editar sala/ano' : 'Nova sala/ano')

@section('content')
    <div class="page-header">
        <h1>{{ $year->exists ? 'Editar sala/ano' : 'Nova sala/ano' }}</h1>
        <a href="{{ route('staff.years.index') }}" class="btn btn-light">Voltar</a>
    </div>

    <div class="card" style="max-width:560px">
        <form method="POST" action="{{ $year->exists ? route('staff.years.update', $year) : route('staff.years.store') }}">
            @csrf
            @if($year->exists) @method('PUT') @endif
            <div class="field">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" required maxlength="80" placeholder="Ex.: 5º Ano"
                       value="{{ old('name', $year->name) }}" class="@error('name') is-invalid @enderror">
            </div>
            <div class="field">
                <label class="check"><input type="checkbox" name="active" value="1" @checked(old('active', $year->active))> Ativa</label>
            </div>
            <button type="submit" class="btn">Salvar</button>
        </form>
    </div>
@endsection
