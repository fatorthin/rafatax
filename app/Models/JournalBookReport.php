<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalBookReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'description',
        'journal_book_id',
        'debit_amount',
        'credit_amount',
        'coa_id',
        'transaction_date',
    ];

    public function journal_book()
    {
        return $this->belongsTo(JournalBookReference::class, 'journal_book_id');
    }

    public function coa()
    {
        return $this->belongsTo(Coa::class);
    }
}
