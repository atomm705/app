<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsVerification extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'last_sent_at',
    ];

    protected $dates = [
        'expires_at',
    ];
}
