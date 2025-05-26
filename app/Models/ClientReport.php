<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientReport extends Model
{
    use SoftDeletes;

    protected $table = 'client_report';
    protected $fillable = [
        'client_id',
        'staff_id',
        'report_date',
        'report_content',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
