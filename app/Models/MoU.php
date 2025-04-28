<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MoU extends Model
{
    use SoftDeletes, HasFactory;
    protected $table = 'mous';

    protected $fillable = [
        'mou_number',
        'description',
        'start_date',
        'end_date',
        'client_id',
        'cash_reference_id',
        'status',
        'type'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function cashReference()
    {
        return $this->belongsTo(CashReference::class);
    }

    public function cost_lists()
    {
        return $this->hasMany(CostListMou::class, 'mou_id');
    }
}
