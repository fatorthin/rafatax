<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'resource',
        'action',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Get permission name in format: resource.action
     */
    public function getFullNameAttribute(): string
    {
        return $this->resource . '.' . $this->action;
    }

    /**
     * Scope untuk filter berdasarkan resource
     */
    public function scopeForResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope untuk filter berdasarkan action
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
