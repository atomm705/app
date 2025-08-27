<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyVisit extends Model
{
    protected $connection = 'legacy';
    protected $table = 'patients';
}
