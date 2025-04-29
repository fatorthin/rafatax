<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        return $this->morphTo();
    }

    public static function log($action, $model = null, $oldData = null, $newData = null)
    {
        $userId = null;
        if (Auth::check()) {
            $userId = Auth::id();
        }

        $ipAddress = null;
        $userAgent = null;
        
        if (app()->runningInConsole()) {
            $ipAddress = 'console';
            $userAgent = 'artisan';
        } else {
            $ipAddress = Request::ip();
            $userAgent = Request::userAgent();
        }

        $data = [
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ];

        return static::create($data);
    }
}
