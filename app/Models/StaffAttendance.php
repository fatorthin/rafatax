<?php

namespace App\Models;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffAttendance extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'tanggal',
        'status',
        'is_late',
        'jam_masuk',
        'jam_pulang',  
        'durasi_lembur',
        'visit_solo_count',
        'visit_luar_solo_count',
        'keterangan',
    ];  

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }    
}
