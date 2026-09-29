<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use Auditable;

    protected $table = 'classes';

    protected $fillable = ['school_year_id', 'name', 'teacher_id', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'meeting_classes', 'class_id', 'meeting_id');
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class, 'class_id');
    }

    public function auditEntity(): string
    {
        return 'class';
    }

    public function auditFields(): array
    {
        return ['name' => 'Nome', 'school_year_id' => 'Sala/Ano', 'teacher_id' => 'Professora', 'active' => 'Ativa'];
    }

    protected function auditValue(string $field, mixed $value): string
    {
        return match ($field) {
            'school_year_id' => (string) SchoolYear::whereKey($value)->value('name'),
            'teacher_id' => (string) User::whereKey($value)->value('name'),
            default => $this->defaultAuditValue($field, $value),
        };
    }
}
