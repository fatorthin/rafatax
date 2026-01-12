<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class CostListMou extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;
    protected $table = 'cost_list_mous';

    protected $fillable = [
        'mou_id',
        'coa_id',
        'quantity',
        'satuan_quantity',
        'amount',
        'total_amount',
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
