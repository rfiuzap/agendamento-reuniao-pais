<?php

namespace App\Support;

class AppVersion
{
    public static function label(): string
    {
        return 'Versão '.config('reuniao.version');
    }
}
