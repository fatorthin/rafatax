<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CostListMou extends Model
{
    use SoftDeletes, HasFactory;
    protected $table = 'cost_list_mous';

    protected $fillable = [
        'mou_id',
        'coa_id',
        'amount',
        'description'
    ];

    public function mou()
    {
        return $this->belongsTo(MoU::class);
    }

    public function coa()
    {
        return $this->belongsTo(Coa::class);
    }
}
