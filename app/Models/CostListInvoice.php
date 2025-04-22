<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostListInvoice extends Model
{
    use SoftDeletes;

    protected $table = 'cost_list_invoices';

    protected $fillable = ['mou_id', 'invoice_id', 'coa_id', 'amount', 'description'];

    public function mou()
    {
        return $this->belongsTo(MoU::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function coa()
    {
        return $this->belongsTo(CoA::class);
    }
}
