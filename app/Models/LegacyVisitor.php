<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyVisitor extends Model
{
    protected $connection = 'legacy';
    protected $table = 'visitors';

    public function visits(){
        return $this->hasMany(LegacyVisit::class, 'visitor_id', 'id');
    }

}
