<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsVerification extends Model
{
    protected $fillable = ['phone', 'code', 'expires_at'];

    public $timestamps = true;

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
