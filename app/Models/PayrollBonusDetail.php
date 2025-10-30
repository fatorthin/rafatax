<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollBonusDetail extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'payroll_bonus_id',
        'staff_id',
        'amount',
        'case_project_detail_ids',
    ];

    protected $casts = [
        'case_project_detail_ids' => 'array',
    ];

    public function payrollBonus()
    {
        return $this->belongsTo(PayrollBonus::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
