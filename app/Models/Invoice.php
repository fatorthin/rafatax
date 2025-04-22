<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = ['mou_id', 'invoice_number', 'invoice_date', 'due_date', 'invoice_status'];

    public function mou()
    {
        return $this->belongsTo(MoU::class);
    }

    public function costListInvoices()
    {
        return $this->hasMany(CostListInvoice::class, 'invoice_id');
    }
}
