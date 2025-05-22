<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryMou extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'format_mou_pt',
        'format_mou_kkp',
    ];
}
