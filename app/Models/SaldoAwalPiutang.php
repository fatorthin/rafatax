<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class SaldoAwalPiutang extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'saldo_awal_piutangs';

    protected $fillable = [
        'client_id',
        'amount',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
