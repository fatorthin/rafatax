<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class Client extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;
    protected $table = 'clients';
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'contact_person',
        'npwp'
    ];
    
    public function mous()
    {
        return $this->hasMany(MoU::class);
    }
}
