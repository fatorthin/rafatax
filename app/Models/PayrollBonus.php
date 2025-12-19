<?php

namespace App\Models;

use App\Traits\LogsActivity;
use App\Models\CaseProjectDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollBonus extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'description',
        'payroll_date',
        'case_project_ids',
    ];

    protected $casts = [
        'case_project_ids' => 'array',
    ];

    protected static function booted()
    {
        static::saved(function (PayrollBonus $record) {
            if ($record->wasChanged('case_project_ids') || $record->wasRecentlyCreated) {
                $record->syncDetails();
            }
        });
    }

    public function syncDetails()
    {
        // Remove existing details
        $this->details()->delete();

        $caseProjectIds = $this->case_project_ids ?? [];

        if (empty($caseProjectIds)) {
            return;
        }

        // Aggregate details from selected case projects
        $caseProjectDetails = CaseProjectDetail::whereIn('case_project_id', $caseProjectIds)->get();

        if ($caseProjectDetails->isEmpty()) {
            return;
        }

        $groupedData = $caseProjectDetails->groupBy('staff_id')->map(function ($details) {
            return [
                'total_bonus' => $details->sum('bonus'),
                'case_project_detail_ids' => $details->pluck('id')->values()->toArray(),
            ];
        });

        foreach ($groupedData as $staffId => $data) {
            $this->details()->create([
                'staff_id' => $staffId,
                'amount' => $data['total_bonus'],
                'case_project_detail_ids' => $data['case_project_detail_ids'],
            ]);
        }
    }

    public function details()
    {
        return $this->hasMany(PayrollBonusDetail::class);
    }
}
