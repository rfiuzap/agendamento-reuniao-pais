{{-- Expects: $slots, $action (form URL), optional $currentSlotId, $method --}}
<div class="legend">
    <span class="l-available">Disponível</span>
    <span class="l-booked">Reservado</span>
    @isset($currentSlotId)<span class="l-current">Seu horário atual</span>@endisset
</div>

@if($slots->isEmpty())
    <div class="alert alert-info">Não há horários cadastrados para esta turma.</div>
@else
    <form method="POST" action="{{ $action }}" data-once>
        @csrf
        @isset($method) @method($method) @endisset
        <div class="slot-grid" role="list">
            @foreach($slots as $slot)
                @php($range = substr($slot->start_time, 0, 5).' às '.substr($slot->end_time, 0, 5))
                @if(isset($currentSlotId) && $slot->id === $currentSlotId)
                    <div class="slot slot-current" role="listitem">
                        <span class="slot-time">{{ $slot->label() }}</span>
                        <span class="slot-status">Atual</span>
                    </div>
                @elseif($slot->isAvailable())
                    <button type="submit" name="slot_id" value="{{ $slot->id }}" class="slot slot-available" role="listitem"
                            aria-label="{{ $range }} - Disponível">
                        <span class="slot-time">{{ $slot->label() }}</span>
                        <span class="slot-status">Disponível</span>
                    </button>
                @else
                    <div class="slot slot-booked" role="listitem" aria-label="{{ $range }} - Reservado">
                        <span class="slot-time">{{ $slot->label() }}</span>
                        <span class="slot-status">Reservado</span>
                    </div>
                @endif
            @endforeach
        </div>
    </form>
@endif
