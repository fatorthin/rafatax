<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OvertimeCount extends Model
{
    use SoftDeletes;

    protected $table = 'overtime_counts';

    protected $fillable = [
        'staff_id',
        'overtime_date',
        'overtime_count',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
