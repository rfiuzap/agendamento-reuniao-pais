@foreach(['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info'] as $key => $class)
    @if(session($key))
        <div class="alert alert-{{ $class }}" role="{{ $key === 'error' ? 'alert' : 'status' }}">{{ session($key) }}</div>
    @endif
@endforeach
@if($errors->any() && ! ($hideErrorSummary ?? false))
    <div class="alert alert-error" role="alert">
        @if($errors->count() === 1)
            {{ $errors->first() }}
        @else
            Verifique os campos destacados:
            <ul class="mb-0">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
