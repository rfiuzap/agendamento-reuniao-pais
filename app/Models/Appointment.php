<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use Auditable;

    public const STATUSES = [
        'confirmed' => 'Confirmado',
        'cancelled' => 'Cancelado',
    ];

    protected $fillable = [
        'meeting_id', 'time_slot_id', 'responsible_email', 'responsible_name',
        'student_id', 'student_name', 'status', 'active_slot_id', 'active_meeting_id',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('appointments.status', 'confirmed');
    }

    public function isActive(): bool
    {
        return $this->status === 'confirmed';
    }

    public function auditEntity(): string
    {
        return 'appointment';
    }

    public function auditTitle(): string
    {
        return $this->student_name.' ('.$this->responsible_email.')';
    }

    public function auditFields(): array
    {
        return ['responsible_name' => 'Responsável', 'student_name' => 'Aluno', 'time_slot_id' => 'Horário', 'status' => 'Status'];
    }

    protected function auditValue(string $field, mixed $value): string
    {
        if ($field === 'time_slot_id') {
            $slot = $value ? TimeSlot::with('schoolClass.schoolYear', 'meeting')->find($value) : null;

            return $slot ? $slot->meeting->date->format('d/m').' · '.$slot->schoolClass?->fullName().' · '.$slot->label() : '';
        }

        return $field === 'status' ? (self::STATUSES[$value] ?? (string) $value) : $this->defaultAuditValue($field, $value);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
