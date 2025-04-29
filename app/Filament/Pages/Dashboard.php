<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InvoiceStats;
use App\Filament\Widgets\MouInvoicesTable;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
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
} 