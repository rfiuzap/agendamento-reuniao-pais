<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Auditable, Notifiable;

    public function auditEntity(): string
    {
        return 'user';
    }

    public function auditFields(): array
    {
        return ['name' => 'Nome', 'email' => 'E-mail', 'username' => 'Usuário', 'role' => 'Perfil', 'active' => 'Ativo', 'password' => 'Senha'];
    }

    public function auditHidden(): array
    {
        return ['password'];
    }

    protected function auditValue(string $field, mixed $value): string
    {
        return $field === 'role' ? (self::ROLES[$value] ?? (string) $value) : $this->defaultAuditValue($field, $value);
    }

    public const ROLE_ADMIN = 'admin';
    public const ROLE_COORDINATOR = 'coordinator';
    public const ROLE_TEACHER = 'teacher';

    public const ROLES = [
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_COORDINATOR => 'Coordenadora',
        self::ROLE_TEACHER => 'Professora',
    ];

    protected $fillable = ['name', 'email', 'username', 'password', 'role', 'active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id')->orderBy('name');
    }

    public function scopeTeachers(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_TEACHER);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_TEACHER => 'staff.teacher.dashboard',
            self::ROLE_COORDINATOR => 'staff.appointments.index',
            default => 'staff.dashboard',
        };
    }
}
