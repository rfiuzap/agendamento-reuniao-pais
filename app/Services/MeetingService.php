<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Meeting;
use App\Models\SchoolClass;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class MeetingService
{
    /**
     * Creates or updates a meeting, its classes and time slots.
     * Slots holding an active appointment are never removed or moved.
     */
    public function save(Meeting $meeting, array $data, array $classIds): Meeting
    {
        $data['start_time'] = SlotGenerator::normalize($data['start_time']);
        $data['end_time'] = SlotGenerator::normalize($data['end_time']);
        $classIds = array_values(array_unique(array_map('intval', $classIds)));

        return DB::transaction(function () use ($meeting, $data, $classIds) {
            $names = fn (array $ids) => SchoolClass::with('schoolYear')->whereIn('id', $ids)->get()->map->fullName()->sort(SORT_NATURAL)->join(', ');
            $before = $meeting->exists ? $names($meeting->classes()->pluck('classes.id')->all()) : '';
            $after = $names($classIds);
            if ($before !== $after) {
                $meeting->auditExtra = [['campo' => 'Turmas participantes', 'antes' => $before, 'depois' => $after]];
            }

            $meeting->fill($data)->save();
            $meeting->flushAudit(); // only the class list changed: the model fired no event
            $meeting->classes()->sync($classIds);
            $this->syncSlots($meeting->fresh(), $classIds);

            return $meeting;
        });
    }

    public function delete(Meeting $meeting): void
    {
        if ($meeting->appointments()->exists()) {
            throw new BusinessRuleException('Esta reunião possui agendamentos e não pode ser excluída. Altere o status para "Encerrada".');
        }

        DB::transaction(fn () => $meeting->delete());
    }

    private function syncSlots(Meeting $meeting, array $classIds): void
    {
        $desired = collect(SlotGenerator::generate(
            $meeting->start_time, $meeting->end_time, $meeting->duration_minutes, $meeting->break_minutes
        ))->keyBy('start');

        $existing = TimeSlot::where('meeting_id', $meeting->id)
            ->with('activeAppointment')
            ->lockForUpdate()
            ->get();

        foreach ($existing as $slot) {
            $wanted = in_array($slot->class_id, $classIds, true) ? $desired->get($slot->start_time) : null;

            if ($wanted && $wanted['end'] === $slot->end_time) {
                continue;
            }

            if ($slot->activeAppointment) {
                $className = SchoolClass::find($slot->class_id)?->name;
                throw new BusinessRuleException(
                    "Não é possível remover ou alterar o horário {$slot->label()} da turma {$className}: existe um agendamento ativo. Cancele ou altere o agendamento antes."
                );
            }

            $wanted ? $slot->update(['end_time' => $wanted['end']]) : $slot->delete();
        }

        $present = $existing->filter->exists
            ->map(fn (TimeSlot $s) => $s->class_id.'|'.$s->start_time)
            ->flip();

        $rows = [];
        foreach ($classIds as $classId) {
            foreach ($desired as $slot) {
                if (! $present->has($classId.'|'.$slot['start'])) {
                    $rows[] = [
                        'meeting_id' => $meeting->id,
                        'class_id' => $classId,
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                        'status' => 'available',
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            TimeSlot::insert($chunk);
        }
    }
}
