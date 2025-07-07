<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepartmentReference extends Model
{
    use SoftDeletes;

    protected $table = 'department_references';

    protected $fillable = [
        'name',
        'description'
    ];
}
