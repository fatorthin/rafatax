<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceReviewReference extends Model
{
    use SoftDeletes;

    protected $table = 'performance_review_references';
    protected $fillable = [
        'name',
        'description',
        'group',
        'type',
    ];

    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'performance_review_reference_staff');
    }
}
