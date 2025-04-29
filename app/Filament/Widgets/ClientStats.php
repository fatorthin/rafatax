<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Client', Client::count())
                ->description('Total semua client')
                ->icon('heroicon-o-users')
                ->color('primary'),
            Stat::make('New Clients', Client::whereMonth('created_at', now()->month)->count())
                ->description('Client baru bulan ini')
                ->icon('heroicon-o-user-plus')
                ->color('info'),
        ];
    }
} 