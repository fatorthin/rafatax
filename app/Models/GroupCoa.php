<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupCoa extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id', 'name'
    ];
    
    
}
