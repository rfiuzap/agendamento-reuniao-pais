<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const DEFAULTS = [
        'school_name' => 'Colégio Morumbi',
        'contact_email' => '',
        'contact_phone' => '',
        'parent_instructions' => 'Escolha a reunião, a turma do seu filho e um horário disponível.',
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

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            self::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings');
    }
}
