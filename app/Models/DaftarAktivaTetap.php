<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class DaftarAktivaTetap extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'deskripsi',
        'tahun_perolehan',
        'harga_perolehan',
        'tarif_penyusutan',
        'status',
    ];
}
