<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyFacility extends Model
{
    protected $connection = 'legacy';
    protected $table = 'facilities';

}
