<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChecklistMou extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mou_id',
        'invoice_id',
        'checklist_date',
        'status',
        'notes',
    ];

    public function mou()
    {
        return $this->belongsTo(MoU::class, 'mou_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
