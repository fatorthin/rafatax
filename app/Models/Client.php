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

    public function mous()
    {
        return $this->hasMany(MoU::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class);
    }
}
