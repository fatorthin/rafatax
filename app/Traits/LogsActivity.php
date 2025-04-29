<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        // Skip logging during seeding
        if (app()->runningInConsole() && app()->environment('local')) {
            return;
        }

        static::created(function ($model) {
            ActivityLog::log('create', $model, null, $model->toArray());
        });

        static::updated(function ($model) {
            ActivityLog::log('update', $model, $model->getOriginal(), $model->toArray());
        });

        static::deleted(function ($model) {
            // Only log if it's not a soft delete
            if (!method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                ActivityLog::log('delete', $model, $model->toArray(), null);
            }
        });

        // Add logging for soft deletes
        if (method_exists(static::class, 'bootSoftDeletes')) {
            static::restored(function ($model) {
                ActivityLog::log('restore', $model, null, $model->toArray());
            });
        }
    }
} 