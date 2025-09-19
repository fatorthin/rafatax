<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class RoleUser extends Model
{
    protected $table = 'role_user';

    protected $fillable = [
        'role_id',
        'user_id',
    ];

    use LogsActivity;

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
