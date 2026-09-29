<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Mail\AccessCodeMail;
use App\Models\AuthenticationCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ParentAuthService
{
    public function sendCode(string $email): void
    {
        $email = AppointmentService::normalizeEmail($email);
        $cooldown = config('reuniao.code_resend_seconds');

        $last = AuthenticationCode::where('email', $email)->latest('id')->first();
        if ($last && $last->created_at->gt(now()->subSeconds($cooldown))) {
            $wait = $cooldown - (int) $last->created_at->diffInSeconds(now());
            throw new BusinessRuleException("Aguarde {$wait} segundos para solicitar um novo código.");
        }

        $sentLastHour = AuthenticationCode::where('email', $email)->where('created_at', '>', now()->subHour())->count();
        if ($sentLastHour >= config('reuniao.code_max_per_hour')) {
            throw new BusinessRuleException('Muitas solicitações de código. Tente novamente mais tarde.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($email, $code) {
            AuthenticationCode::where('email', $email)->whereNull('used_at')->update(['used_at' => now()]);
            AuthenticationCode::create([
                'email' => $email,
                'code_hash' => $this->hash($email, $code),
                'expires_at' => now()->addMinutes(config('reuniao.code_ttl_minutes')),
            ]);
        });

        Mail::to($email)->send(new AccessCodeMail($code));
    }

    /** Validates the code and consumes it; throws with a user-facing message on failure. */
    public function verify(string $email, string $code): void
    {
        $email = AppointmentService::normalizeEmail($email);

        // The error is returned (not thrown) so the attempt counter is committed.
        $error = DB::transaction(function () use ($email, $code) {
            $record = AuthenticationCode::where('email', $email)
                ->whereNull('used_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $record || $record->expires_at->isPast()) {
                return 'Código expirado ou inexistente. Solicite um novo código.';
            }

            $max = config('reuniao.code_max_attempts');
            if ($record->attempts >= $max) {
                return 'Número máximo de tentativas atingido. Solicite um novo código.';
            }

            if (! hash_equals($record->code_hash, $this->hash($email, trim($code)))) {
                $record->increment('attempts');
                $left = $max - $record->attempts;

                return $left > 0
                    ? "Código incorreto. Você ainda tem {$left} tentativa(s)."
                    : 'Número máximo de tentativas atingido. Solicite um novo código.';
            }

            $record->update(['used_at' => now()]);

            return null;
        });

        if ($error) {
            throw new BusinessRuleException($error);
        }
    }

    private function hash(string $email, string $code): string
    {
        return hash_hmac('sha256', $email.'|'.$code, config('app.key'));
    }
}
