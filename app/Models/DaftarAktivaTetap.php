<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class DaftarAktivaTetap extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'deskripsi',
        'tahun_perolehan',
        'harga_perolehan',
        'tarif_penyusutan',
    ];
    
}
