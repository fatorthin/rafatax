<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseProjectDetail extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'case_project_id',
        'staff_id',
        'bonus',
    ];

    public function caseProject()
    {
        return $this->belongsTo(CaseProject::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
