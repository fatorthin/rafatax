<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use SoftDeletes, HasFactory;

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
