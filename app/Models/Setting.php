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
        'brand_logo' => '',
    ];

    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function allValues(): array
    {
        $stored = Cache::rememberForever('settings', function () {
            try {
                return self::query()->pluck('value', 'key')->all();
            } catch (\Throwable) {
                return [];
            }
        });

        // Defaults merged on every read, so options added in new versions exist even with an old cache.
        return array_merge(self::DEFAULTS, $stored);
    }

    public static function get(string $key): ?string
    {
        return self::allValues()[$key] ?? null;
    }

    /** Square icon ("logo" key, kept for compatibility): favicon, app icon and staff sidebar. */
    public static function logoUrl(): string
    {
        $logo = self::get('logo');

        return $logo && is_file(public_path('uploads/'.$logo))
            ? asset('uploads/'.$logo)
            : asset('img/logo.svg');
    }

    /** School logo shown in headers; falls back to the icon while none is uploaded. */
    public static function brandLogoUrl(): string
    {
        $logo = self::get('brand_logo');

        return $logo && is_file(public_path('uploads/'.$logo)) ? asset('uploads/'.$logo) : self::logoUrl();
    }

    public const LABELS = [
        'school_name' => 'Nome da escola',
        'contact_email' => 'E-mail de contato',
        'contact_phone' => 'Telefone de contato',
        'parent_instructions' => 'Instruções aos responsáveis',
        'logo' => 'Ícone',
        'brand_logo' => 'Logo',
    ];

    public static function put(array $values, bool $audit = true): void
    {
        $before = self::allValues();
        $changes = [];

        foreach ($values as $key => $value) {
            self::updateOrCreate(['key' => $key], ['value' => $value]);

            if ((string) ($before[$key] ?? '') !== (string) $value) {
                $show = fn ($v) => in_array($key, ['logo', 'brand_logo'], true) ? ($v ? 'nova imagem enviada' : 'padrão') : (string) $v;
                $changes[] = ['campo' => self::LABELS[$key] ?? $key, 'antes' => $show($before[$key] ?? ''), 'depois' => $show($value)];
            }
        }
        Cache::forget('settings');

        if ($audit && $changes) {
            AuditLogger::record('settings', 'updated', null, 'Configurações', $changes);
        }
    }
}
