<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyVisitInfo extends Model
{
    protected $connection = 'legacy';
    protected $table = 'visit_info';

}
