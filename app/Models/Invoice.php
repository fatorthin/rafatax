<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class Invoice extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;

    protected $table = 'invoices';

    protected $fillable = [
        'mou_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'amount',
        'invoice_status',
        'invoice_type',
        'description',
        'is_saldo_awal',
        'memo_id',
        'rek_transfer'
    ];

    protected static function booted()
    {
        static::deleted(function ($invoice) {
            // Reset checklist invoices
            \App\Models\ChecklistMou::where('invoice_id', $invoice->id)->update([
                'invoice_id' => null,
                'status' => 'pending'
            ]);
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function mou()
    {
        return $this->belongsTo(MoU::class);
    }

    public function costList()
    {
        return $this->hasMany(CostListInvoice::class);
    }

    public function costListInvoices()
    {
        return $this->hasMany(CostListInvoice::class);
    }

    public function memo()
    {
        return $this->belongsTo(Memo::class);
    }
}
