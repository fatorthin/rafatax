<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class CashReference extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;
    protected $table = 'cash_references';
    protected $fillable = [
        'name',
        'description',
        'reference_number',
        'amount',
        'date',
        'type'
    ];

    public function cashReports()
    {
        return $this->hasMany(CashReport::class);
    }
}
