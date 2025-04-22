<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashReference extends Model
{
    use SoftDeletes;
    protected $table = 'cash_references';
    protected $fillable = [
        'name',
        'description',
    ];
}
