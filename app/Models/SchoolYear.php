<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolYear extends Model
{
    use Auditable;

    protected $fillable = ['name', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class)->orderBy('name');
    }

    public function auditEntity(): string
    {
        return 'school_year';
    }

    public function auditFields(): array
    {
        return ['name' => 'Nome', 'active' => 'Ativa'];
    }
}
