<?php

namespace App\Filament\App\Widgets;

use App\Models\MoU;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MouOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $currentYear = now()->year;

        $totalMou = MoU::whereYear('start_date', $currentYear)->count();
        $approvedMou = MoU::whereYear('start_date', $currentYear)->where('status', 'approved')->count();
        $unapprovedMou = MoU::whereYear('start_date', $currentYear)->where('status', 'unapproved')->count();
        $ptMou = MoU::whereYear('start_date', $currentYear)->where('type', 'pt')->count();
        $kkpMou = MoU::whereYear('start_date', $currentYear)->where('type', 'kkp')->count();

        return [
            Stat::make('Total MoU Tahun Ini', $totalMou)
                ->description('Total MoU tahun ' . $currentYear)
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('MoU Disetujui', $approvedMou)
                ->description('MoU yang sudah disetujui')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('MoU Belum Disetujui', $unapprovedMou)
                ->description('MoU yang belum disetujui')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('MoU PT', $ptMou)
                ->description('MoU tipe PT')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),

            Stat::make('MoU KKP', $kkpMou)
                ->description('MoU tipe KKP')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('gray'),
        ];
    }
}

