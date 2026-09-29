<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const ENTITIES = [
        'appointment' => 'Agendamento',
        'meeting' => 'Reunião',
        'class' => 'Turma',
        'school_year' => 'Sala/Ano',
        'user' => 'Usuário',
        'settings' => 'Configurações',
    ];

    public const ACTIONS = [
        'created' => 'Cadastro',
        'updated' => 'Alteração',
        'deleted' => 'Exclusão',
        'login' => 'Acesso ao sistema',
    ];

    protected $fillable = ['user_id', 'actor', 'action', 'entity', 'entity_id', 'data', 'ip'];

    protected $casts = ['data' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function heading(): string
    {
        if ($this->action === 'login') {
            return self::ACTIONS['login'];
        }

        return (self::ACTIONS[$this->action] ?? ucfirst($this->action)).' · '.(self::ENTITIES[$this->entity] ?? $this->entity);
    }

    public function title(): ?string
    {
        return $this->action === 'login' ? null : ($this->data['titulo'] ?? null);
    }

    /** @return list<array{campo: string, antes: string, depois: string}> */
    public function changes(): array
    {
        return $this->data['alteracoes'] ?? [];
    }

    public function badgeClass(): string
    {
        return ['created' => 'badge-green', 'deleted' => 'badge-red', 'login' => ''][$this->action] ?? 'badge-blue';
    }
}
