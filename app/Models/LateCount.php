<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LateCount extends Model
{
    use SoftDeletes;
    protected $table = 'late_counts';

    protected $fillable = [
        'staff_id',
        'late_count',
        'late_date',
        'is_verified',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
