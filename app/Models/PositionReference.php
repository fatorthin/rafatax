<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class PositionReference extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'position_references';

    protected $fillable = [
        'name',
        'description',
        'salary',
    ];
}
