<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class Coa extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;
    protected $table = 'coa';

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'status',
        'group_coa_id'
    ];

    public function groupCoa()
    {
        return $this->belongsTo(GroupCoa::class);
    }
}
