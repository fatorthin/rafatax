<?php

namespace App\Filament\App\Pages;

use App\Filament\Pages\ChecklistBuktiPotong as BasePage;

class ChecklistBuktiPotong extends BasePage
{
    protected static ?string $navigationGroup = 'Keuangan';

    public static function canAccess(array $parameters = []): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('cash-manager')
            || $user->hasRole('admin')
            || $user->hasPermission('checklist-bukti-potong.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
