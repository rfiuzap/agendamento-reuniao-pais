<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(string $action, string $entity, ?int $entityId = null, array $data = [], ?string $actor = null): void
    {
        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user?->id,
            'actor' => $actor ?? $user?->username,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'data' => $data ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
