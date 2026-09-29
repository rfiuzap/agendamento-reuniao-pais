<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = ['name', 'responsible_email'];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
