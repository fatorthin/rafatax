<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InvoiceStats;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // protected static string $routePath = 'admin';
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';


    public function getHeaderWidgets(): array
    {
        return [
            InvoiceStats::class,
        ];
    }

    public function getWidgets(): array
    {
        return [];
    }

    public function mount(): void
    {
        if (request()->session()->has('success')) {
            Notification::make()
                ->title(request()->session()->get('success'))
                ->send();
        }
        if (request()->session()->has('error')) {
            Notification::make()
                ->title(request()->session()->get('error'))
                ->danger()
                ->send();
        }
    }
}
