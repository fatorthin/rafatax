<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coa extends Model
{
    use SoftDeletes;
    protected $table = 'coa';

    protected $fillable = [
        'code',
        'name',
        'type',
    ];
}
