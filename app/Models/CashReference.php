<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class CashReference extends Model implements Sortable
{
    use SoftDeletes, HasFactory, LogsActivity, SortableTrait;
    protected $table = 'cash_references';
    protected $fillable = [
        'name',
        'description',
        'status_kepemilikan',
        'sort_order',
    ];

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];

    public function getKepemilikanAttribute(): ?string
    {
        return $this->status_kepemilikan;
    }

    public function cashReports()
    {
        return $this->hasMany(CashReport::class);
    }
}
