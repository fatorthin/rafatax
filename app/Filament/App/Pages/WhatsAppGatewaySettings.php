<?php

namespace App\Filament\App\Pages;

use App\Filament\Pages\WhatsAppGatewaySettings as BasePage;

class WhatsAppGatewaySettings extends BasePage
{
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 99;

    public static function canAccess(array $parameters = []): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('cash-manager')
            || $user->hasPermission('manage_whatsapp_gateway');
    }
}
