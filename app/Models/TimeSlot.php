<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TimeSlot extends Model
{
    public $timestamps = false;

    protected $fillable = ['meeting_id', 'class_id', 'start_time', 'end_time', 'status'];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function activeAppointment(): HasOne
    {
        return $this->hasOne(Appointment::class, 'active_slot_id');
    }

    public function label(): string
    {
        return substr($this->start_time, 0, 5);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}
