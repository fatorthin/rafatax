<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class GroupCoa extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'id',
        'name'
    ];
}
