@php($labels = [1 => 'Reunião', 2 => 'Dados do aluno', 3 => 'Horário', 4 => 'Confirmação'])
<div class="step-label">Etapa {{ $current }} de 4 · {{ $labels[$current] }}</div>
<ol class="steps" aria-hidden="true">
    @foreach($labels as $i => $label)
        <li class="{{ $i <= $current ? 'done' : '' }}"></li>
    @endforeach
</ol>
