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
        'client_name',
        'mou_id',
        'memo_id',
        'case_letter_number',
        'case_letter_date',
        'power_of_attorney_number',
        'power_of_attorney_date',
        'filling_drive',
        'report_date',
        'share_client_date',
        'link_drive'
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

    public function memo()
    {
        return $this->belongsTo(Memo::class);
    }

    public function details()
    {
        return $this->hasMany(CaseProjectDetail::class);
    }

    public function powerOfAttorney()
    {
        return $this->hasOne(CaseProjectPowerOfAttorney::class, 'case_project_id');
    }

    public function getClientDisplayNameAttribute(): string
    {
        return $this->client?->company_name ?? $this->client_name ?? '-';
    }

    public function getReferenceNumberAttribute(): ?string
    {
        return $this->mou?->mou_number ?? $this->memo?->no_memo;
    }

    protected static function booted()
    {
        static::saving(function (CaseProject $caseProject) {
            if (!empty($caseProject->client_id)) {
                $caseProject->client_name = null;
            }
            if (!empty($caseProject->memo_id)) {
                $caseProject->mou_id = null;
            } elseif (!empty($caseProject->mou_id)) {
                $caseProject->memo_id = null;
            }
        });
    }
}
