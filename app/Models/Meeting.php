<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    public const STATUSES = [
        'draft' => 'Rascunho',
        'published' => 'Liberada',
        'closed' => 'Encerrada',
    ];

    protected $fillable = ['name', 'date', 'start_time', 'end_time', 'duration_minutes', 'break_minutes', 'status'];

    protected $casts = [
        'date' => 'date',
        'duration_minutes' => 'integer',
        'break_minutes' => 'integer',
    ];

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'meeting_classes', 'meeting_id', 'class_id')->orderBy('name');
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class)->orderBy('start_time');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** Meetings a responsible can currently book. */
    public function scopeBookable(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereDate('date', '>=', Carbon::today());
    }

    public function isBookable(): bool
    {
        return $this->status === 'published' && $this->date->gte(Carbon::today());
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function timeRange(): string
    {
        return substr($this->start_time, 0, 5).' às '.substr($this->end_time, 0, 5);
    }
}
