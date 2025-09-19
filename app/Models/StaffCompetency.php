<?php

namespace App\Models;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;


class StaffCompetency extends Model
{
    use SoftDeletes;
    use LogsActivity;

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
