<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsVerification extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'last_sent_at',
        'expires_at',
    ];

    protected $casts = [
        'last_sent_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $primaryKey = 'phone';
    public $incrementing = false;
    protected $keyType = 'string';
}

