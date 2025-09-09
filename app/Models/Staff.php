<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\LogsActivity;

class Staff extends Model
{
    use SoftDeletes;
    use LogsActivity;
    protected $fillable = [
        'name',
        'birth_place',
        'birth_date',
        'address',
        'email',
        'no_ktp',
        'phone',
        'no_spk',
        'jenjang',
        'jurusan',
        'university',
        'no_ijazah',
        'tmt_training',
        'periode',
        'selesai_training',
        'department_reference_id',
        'position_reference_id',
        'is_active',
        'salary',
        'position_status',
    ];

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class);
    }

    public function trainings()
    {
        return $this->belongsToMany(Training::class, 'training_staff');
    }

    public function departmentReference()
    {
        return $this->belongsTo(DepartmentReference::class, 'department_reference_id');
    }

    public function positionReference()
    {
        return $this->belongsTo(PositionReference::class, 'position_reference_id');
    }
}
