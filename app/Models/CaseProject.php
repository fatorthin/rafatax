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
        'status',
        'staff_id',
        'client_id',
        'link_dokumen',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function details()
    {
        return $this->hasMany(CaseProjectDetail::class);
    }
}
