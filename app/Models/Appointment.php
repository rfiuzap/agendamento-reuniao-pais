<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
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

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
