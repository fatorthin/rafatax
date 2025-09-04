<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_id',
        'staff_id',
        'salary',
        'bonus_position',
        'bonus_competency',
        'overtime_count',
        'visit_solo_count',
        'visit_luar_solo_count',
        'sick_leave_count',
        'halfday_count',
        'leave_count',
        'cut_bpjs_kesehatan',
        'cut_bpjs_ketenagakerjaan',
        'cut_lain',
        'cut_hutang',
        'bonus_lain',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
