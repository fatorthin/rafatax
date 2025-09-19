<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class DepartmentReference extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'department_references';

    protected $fillable = [
        'name',
        'description'
    ];
}
