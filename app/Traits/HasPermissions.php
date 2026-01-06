<?php

namespace App\Traits;

trait HasPermissions
{

    /**
     * Check if user can create records
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('admin')) {
            return true;
        }

        $resourceName = static::getResourceName();
        return $user->hasPermission($resourceName . '.create');
    }

    /**
     * Check if user can edit records
     */
    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('admin')) {
            return true;
        }

        $resourceName = static::getResourceName();
        return $user->hasPermission($resourceName . '.edit');
    }

    /**
     * Check if user can delete records
     */
    public static function canDelete($record): bool
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('admin')) {
            return true;
        }

        $resourceName = static::getResourceName();
        return $user->hasPermission($resourceName . '.delete');
    }

    /**
     * Get resource name from class name
     */
    public static function getResourceName(): string
    {
        $className = class_basename(static::class);
        // Remove 'Resource' suffix and convert to kebab-case
        $resourceName = str_replace('Resource', '', $className);
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $resourceName));
    }

    /**
     * Check if current user has specific permission (for app panel)
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Admin selalu memiliki akses
        if ($user->hasRole('admin')) {
            return true;
        }

        // Get resource name from class
        $resourceName = static::getResourceName();

        // Check if user has view permission for this resource
        return $user->hasPermission($resourceName . '.view') || $user->hasPermission(\Illuminate\Support\Str::plural($resourceName) . '.view');
    }

    /**
     * Check if user can view the resource list
     */
    public static function canViewAny(): bool
    {
        return static::canAccess();
    }
}
