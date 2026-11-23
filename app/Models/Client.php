<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Client extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;
    protected $table = 'clients';
    protected $fillable = [
        'code',
        'company_name',
        'phone',
        'address',
        'owner_name',
        'owner_role',
        'contact_person',
        'npwp',
        'jenis_wp',
        'grade',
        'pph_25_reporting',
        'pph_23_reporting',
        'pph_21_reporting',
        'pph_4_reporting',
        'ppn_reporting',
        'spt_reporting',
        'status',
        'type',
    ];

    public const GRADES = [
        'E1' => 'E1',
        'E2' => 'E2',
        'E3' => 'E3',
        'D' => 'D',
        'C' => 'C',
        'B1' => 'B1',
        'B2' => 'B2',
        'A1' => 'A1',
        'A2' => 'A2',
        'A3' => 'A3',
        'A4' => 'A4',
    ];

    public static function getGradeOptions(?string $currentGrade = null): array
    {
        $grades = self::GRADES;

        if ($currentGrade && !isset($grades[$currentGrade])) {
            $grades[$currentGrade] = $currentGrade;
        }

        return $grades;
    }

    public static function getFilterGradeOptions(): array
    {
        $grades = self::GRADES;

        try {
            $existingGrades = self::query()
                ->whereNotNull('grade')
                ->where('grade', '!=', '')
                ->distinct()
                ->pluck('grade', 'grade')
                ->toArray();

            return array_merge($grades, $existingGrades);
        } catch (\Throwable $e) {
            return $grades;
        }
    }

    public function mous()
    {
        return $this->hasMany(MoU::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class);
    }
}
