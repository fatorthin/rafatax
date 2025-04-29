<?php

namespace App\Filament\Widgets;

use App\Models\MoU;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MouStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total MoU', MoU::count())
                ->description('Total semua MoU')
                ->icon('heroicon-o-document-text')
                ->color('success'),
            Stat::make('Active MoU', MoU::where('status', 'active')->count())
                ->description('MoU yang masih aktif')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Inactive MoU', MoU::where('status', 'inactive')->count())
                ->description('MoU yang tidak aktif')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
} 