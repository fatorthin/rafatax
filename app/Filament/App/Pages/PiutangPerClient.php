<?php

namespace App\Filament\App\Pages;

use App\Filament\Pages\PiutangPerClient as BasePage;
use App\Filament\App\Resources\InvoiceResource;

class PiutangPerClient extends BasePage
{
    protected static ?string $navigationGroup = 'Keuangan';

    public static function canAccess(array $parameters = []): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasPermission('invoice.view') || $user->hasPermission('invoices.view');
    }
}
