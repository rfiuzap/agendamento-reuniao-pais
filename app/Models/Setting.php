<?php

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const DEFAULTS = [
        'school_name' => 'Colégio Morumbi',
        'contact_email' => '',
        'contact_phone' => '',
        'parent_instructions' => 'Escolha a reunião, a turma do seu filho e um horário disponível.',
        'logo' => '',
    ];

    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function allValues(): array
    {
        return Cache::rememberForever('settings', function () {
            try {
                return array_merge(self::DEFAULTS, self::query()->pluck('value', 'key')->all());
            } catch (\Throwable) {
                return self::DEFAULTS;
            }
        });
    }

    public static function get(string $key): ?string
    {
        return self::allValues()[$key] ?? null;
    }

    /** Uploaded logo (public/uploads) or the default one; also used as favicon. */
    public static function logoUrl(): string
    {
        $logo = self::get('logo');

        return $logo && is_file(public_path('uploads/'.$logo))
            ? asset('uploads/'.$logo)
            : asset('img/logo.svg');
    }

    public const LABELS = [
        'school_name' => 'Nome da escola',
        'contact_email' => 'E-mail de contato',
        'contact_phone' => 'Telefone de contato',
        'parent_instructions' => 'Instruções aos responsáveis',
        'logo' => 'Logo',
    ];

    public static function put(array $values, bool $audit = true): void
    {
        $before = self::allValues();
        $changes = [];

        foreach ($values as $key => $value) {
            self::updateOrCreate(['key' => $key], ['value' => $value]);

            if ((string) ($before[$key] ?? '') !== (string) $value) {
                $show = fn ($v) => $key === 'logo' ? ($v ? 'novo logo enviado' : 'logo padrão') : (string) $v;
                $changes[] = ['campo' => self::LABELS[$key] ?? $key, 'antes' => $show($before[$key] ?? ''), 'depois' => $show($value)];
            }
        }
        Cache::forget('settings');

        if ($audit && $changes) {
            AuditLogger::record('settings', 'updated', null, 'Configurações', $changes);
        }
    }
}
