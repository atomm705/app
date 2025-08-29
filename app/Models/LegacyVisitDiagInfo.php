<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyVisitDiagInfo extends Model
{
    protected $connection = 'legacy';
    protected $table = 'visit_diag_info';

}
