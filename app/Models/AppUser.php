<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AppUser extends Authenticatable
{
    use HasApiTokens;

    public function oneVisitor(){
        return $this->hasOne(LegacyVisitor::class, 'id', 'patient_id');
    }

    public function moreVisitors(){

    }
}
