<?php

namespace App\Models;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffAttendance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'staff_id',
        'tanggal',
        'status',
        'is_late',
        'jam_masuk',
        'jam_pulang',  
        'durasi_lembur',
        'is_visit_solo',
        'is_visit_luar_solo',
        'keterangan',
    ];  

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }    
}
