<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class CostListInvoice extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;

    protected $table = 'cost_list_invoices';

    protected $fillable = [
        'invoice_id',
        'description',
        'amount',
        'quantity',
        'total'
    ];

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
