<?php

namespace App\Services;

use App\Http\Middleware\EnsureParentAuthenticated;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  list<array{campo: string, antes: string, depois: string}>  $changes
     */
    public static function record(string $entity, string $action, ?int $entityId, string $title, array $changes = []): void
    {
        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user?->id,
            'actor' => self::actor(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'data' => ['titulo' => $title, 'alteracoes' => $changes],
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    /** Staff user name, the parent's e-mail, or "Sistema" (console, seeders). */
    private static function actor(): string
    {
        if ($user = Auth::user()) {
            return $user->name;
        }

        if (request()->hasSession()) {
            $email = request()->session()->get(EnsureParentAuthenticated::SESSION_EMAIL);
            if ($email) {
                return $email.' (responsável)';
            }
        }

        return 'Sistema';
    }
}
