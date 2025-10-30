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
        'client_id',
        'budget',
        'status',
        'project_date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function details()
    {
        return $this->hasMany(CaseProjectDetail::class);
    }
}
