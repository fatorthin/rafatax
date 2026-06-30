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
        'rek_transfer',
        'is_include_pph23',
        'cash_report_id',
        'tgl_transfer',
        'is_send_invoice',
        'send_invoice_date',
        'client_id',
        'is_pph23_checked',
        'tanggal_bukti_potong_pph23',
        'link_bukti_potong_pph23',
    ];

    protected static function booted()
    {
        static::saving(function ($invoice) {
            if ($invoice->mou_id) {
                $invoice->memo_id = null;
            } elseif ($invoice->memo_id) {
                $invoice->mou_id = null;
            }
        });

        static::saved(function ($invoice) {
            $invoice->costListInvoices()->update([
                'mou_id' => $invoice->mou_id
            ]);
        });

        static::deleted(function ($invoice) {
            // Reset checklist invoices
            \App\Models\ChecklistMou::where('invoice_id', $invoice->id)->update([
                'invoice_id' => null,
                'status' => 'pending'
            ]);
        });
    }

    public function cashReport()
    {
        return $this->belongsTo(CashReport::class);
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

    public function getClientNameAttribute()
    {
        if ($this->client) {
            return $this->client->company_name;
        }
        if ($this->mou && $this->mou->client) {
            return $this->mou->client->company_name;
        }
        if ($this->memo) {
            return $this->memo->nama_klien ?? $this->memo->instansi_klien ?? '';
        }
        return '';
    }

    public function getTotalAmountAttribute()
    {
        if ($this->relationLoaded('costListInvoices')) {
            return $this->costListInvoices->sum('amount');
        }
        return $this->costListInvoices()->sum('amount') ?? 0;
    }
}
