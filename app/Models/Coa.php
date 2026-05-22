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

    protected static function booted(): void
    {
        static::creating(function (self $coa): void {
            if (is_null($coa->sort_order)) {
                $coa->sort_order = (self::max('sort_order') ?? 0) + 1;
            }
        });
    }

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'status',
        'group_coa_id',
        'sort_order'
    ];

    public function groupCoa()
    {
        return $this->belongsTo(GroupCoa::class, 'group_coa_id');
    }
}
