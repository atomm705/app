<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class AppUser extends Authenticatable
{
    use HasApiTokens;
}
