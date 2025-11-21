<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class CategoryMou extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'name',
        'format_mou_pt',
        'format_mou_kkp',
    ];

    public function mous()
    {
        return $this->hasMany(MoU::class, 'category_mou_id');
    }
}
