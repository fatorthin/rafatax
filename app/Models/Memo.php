<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Memo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'description',
        'nama_klien',
        'instansi_klien',
        'alamat_klien',
        'type_work',
        'tanggal_ttd',
        'tipe_klien'
    ];

    protected $casts = ['type_work' => 'array'];
}
