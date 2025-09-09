<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class DepresiasiAktivaTetap extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'daftar_aktiva_tetap_id',
        'tanggal_penyusutan',
        'jumlah_penyusutan',
    ];

    public function daftarAktivaTetap()
    {
        return $this->belongsTo(DaftarAktivaTetap::class);
    }
}
