<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollBonus extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'description',
        'start_date',
        'end_date',
    ];

    public function details()
    {
        return $this->hasMany(PayrollBonusDetail::class);
    }
}
