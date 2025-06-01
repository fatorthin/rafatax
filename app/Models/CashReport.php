<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class CashReport extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;

    protected $table = 'cash_reports';

    protected $fillable = [
        'description',
        'cash_reference_id',
        'mou_id',
        'coa_id',
        'invoice_id',
        'cost_list_invoice_id',
        'type',
        'debit_amount',
        'credit_amount',
        'transaction_date',
    ];

    public function mou()
    {
        return $this->belongsTo(MoU::class);
    }

    public function coa()
    {
        return $this->belongsTo(Coa::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function costListInvoice()
    {
        return $this->belongsTo(CostListInvoice::class);
    }

    public function cashReference()
    {
        return $this->belongsTo(CashReference::class);
    }
}
