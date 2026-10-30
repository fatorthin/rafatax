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

    /**
     * Rekening Bank aktif milik PT.
     */
    public static function getPtBankIds(): array
    {
        return [1, 4]; // BCA PT, BCA 436
    }

    /**
     * Rekening Bank aktif milik KKP.
     */
    public static function getKkpBankIds(): array
    {
        return [2]; // BCA 425 (BCA 140 non-aktif)
    }

    /**
     * Seluruh Rekening Bank aktif.
     */
    public static function getAllBankIds(int $year = 2026): array
    {
        return $year >= 2026 ? [1, 2, 4] : [1, 2, 3, 4];
    }

    /**
     * ID Kas Kecil PT.
     */
    public static function getPtCashLittleId(): int
    {
        return 9; // KAS KECIL PT
    }

    /**
     * ID Kas Kecil KKP.
     */
    public static function getKkpCashLittleId(): int
    {
        return 7; // KAS KECIL KKP
    }

    /**
     * ID Kas Besar KKP.
     */
    public static function getKkpCashBigId(): int
    {
        return 6; // KAS BESAR
    }

    public function cashReports()
    {
        return $this->hasMany(CashReport::class);
    }
}
