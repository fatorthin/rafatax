<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseProject extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'description',
        'case_date',
        'case_type',
        'status',
        'staff_id',
        'client_id',
        'mou_id',
        'case_letter_number',
        'case_letter_date',
        'power_of_attorney_number',
        'power_of_attorney_date',
        'filling_drive',
        'report_date',
        'share_client_date',
    ];

    protected $casts = [
        'staff_id' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function mou()
    {
        return $this->belongsTo(MoU::class);
    }

    public function details()
    {
        return $this->hasMany(CaseProjectDetail::class);
    }
}
