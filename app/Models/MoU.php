<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class MoU extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;
    protected $table = 'mous';

    protected $fillable = [
        'mou_number',
        'description',
        'start_date',
        'end_date',
        'client_id',
        'cash_reference_id',
        'status',
        'type',
        'category_mou_id',
        'percentage_restitution',
        'link_mou',
        'tahun_pajak',
        'tanggal_tagih_awal',
        'tanggal_tagih_akhir',
        'is_send_mou',
        'send_mou_date',
        'discount_amount',
        'is_make_case_project',
        'case_project_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function cashReference()
    {
        return $this->belongsTo(CashReference::class);
    }

    public function cost_lists()
    {
        return $this->hasMany(CostListMou::class, 'mou_id');
    }

    public function feeBulanan()
    {
        return $this->hasMany(CostListMou::class, 'mou_id')->where('coa_id', 119);
    }

    public function feeSpt()
    {
        return $this->hasMany(CostListMou::class, 'mou_id')->where('coa_id', 120);
    }

    public function categoryMou()
    {
        return $this->belongsTo(CategoryMou::class);
    }
    public function checklistMous()
    {
        return $this->hasMany(ChecklistMou::class, 'mou_id');
    }

    public function caseProject()
    {
        return $this->belongsTo(CaseProject::class);
    }
}
