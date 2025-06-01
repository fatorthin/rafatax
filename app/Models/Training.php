<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Training extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'organizer',
        'training_date',
        'expired_date',
        'is_verified',
    ];

    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'training_staff');
    }
}
