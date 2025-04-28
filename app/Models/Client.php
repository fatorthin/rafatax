<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use SoftDeletes, HasFactory;
    protected $table = 'clients';
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'contact_person',
        'npwp'
    ];
}
