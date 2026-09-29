<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthenticationCode extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['email', 'code_hash', 'expires_at', 'used_at', 'attempts'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'attempts' => 'integer',
    ];
}
