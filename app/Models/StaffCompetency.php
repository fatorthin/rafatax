<?php

namespace App\Models;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class StaffCompetency extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'staff_id',
        'competency',
        'date_of_assessment',
        'date_of_expiry',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
