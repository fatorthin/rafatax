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
        'sort_order',
        'is_pph23_checked',
        'tanggal_bukti_potong_pph23'
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

    protected static $isReordering = false;

    protected static function booted()
    {
        static::saved(function ($cashReport) {
            if (self::$isReordering) {
                return;
            }
            self::reorder($cashReport->cash_reference_id, $cashReport->transaction_date);

            if ($cashReport->wasChanged('cash_reference_id') || $cashReport->wasChanged('transaction_date')) {
                $originalCashReferenceId = $cashReport->getOriginal('cash_reference_id') ?? $cashReport->cash_reference_id;
                $originalTransactionDate = $cashReport->getOriginal('transaction_date') ?? $cashReport->transaction_date;
                self::reorder($originalCashReferenceId, $originalTransactionDate);
            }
        });

        static::deleted(function ($cashReport) {
            if (self::$isReordering) {
                return;
            }
            self::reorder($cashReport->cash_reference_id, $cashReport->transaction_date);
        });
    }

    public static function reorder(int|string|null $cashReferenceId, string|null $transactionDate): void
    {
        if (!$cashReferenceId || !$transactionDate) {
            return;
        }

        $date = \Carbon\Carbon::parse($transactionDate);

        self::$isReordering = true;

        try {
            $transactions = self::query()->where('cash_reference_id', $cashReferenceId)
                ->whereYear('transaction_date', $date->year)
                ->whereMonth('transaction_date', $date->month)
                ->orderBy('transaction_date', 'asc')
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($transactions as $index => $report) {
                $newSortOrder = $index + 1;
                if ($report->sort_order !== $newSortOrder) {
                    $report->sort_order = $newSortOrder;
                    $report->saveQuietly();
                }
            }
        } finally {
            self::$isReordering = false;
        }
    }
}

