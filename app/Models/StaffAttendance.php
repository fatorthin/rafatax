<?php

namespace App\Models;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class StaffAttendance extends Model
{
    use SoftDeletes;
    use HasFactory;
    use LogsActivity;

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

    protected static function booted()
    {
        static::saving(function ($attendance) {
            if ($attendance->status === 'sakit') {
                $attendance->jam_masuk = null;
                $attendance->jam_pulang = null;
                $attendance->durasi_lembur = 0;
                $attendance->is_late = false;
            }
        });
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
