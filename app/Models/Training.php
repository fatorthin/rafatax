<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Training extends Model
{
    use SoftDeletes;
    use LogsActivity;
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
