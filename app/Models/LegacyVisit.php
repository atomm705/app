<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyVisit extends Model
{
    protected $connection = 'legacy';
    protected $table = 'visits';

    public function facility(){
        return $this->hasOne(LegacyFacility::class, 'id', 'facility_id');
    }

    public function doctor(){
        return $this->hasOne(User::class, 'id', 'doctor_id');
    }

    public function time_slot(){
        return $this->hasOne(TimeSlot::class, 'id', 'time_slot_id');
    }

    public function visitor(){
        return $this->hasOne(LegacyVisitor::class, 'id', 'visitor_id');
    }

    public function visit_info(){
        return $this->hasOne(LegacyVisitInfo::class, 'visit_id', 'id');
    }

    public function visit_diag_infos(){
        return $this->hasMany(LegacyVisitDiagInfo::class, 'visit_id', 'id');
    }
}
