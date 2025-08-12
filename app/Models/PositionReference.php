<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PositionReference extends Model
{
    use SoftDeletes;

    protected $table = 'position_references';

    protected $fillable = [
        'name',
        'description',
        'salary',
    ];
}
