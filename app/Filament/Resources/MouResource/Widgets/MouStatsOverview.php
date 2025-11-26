<?php

namespace App\Filament\Resources\MouResource\Widgets;

use App\Models\CostListMou;
use App\Models\CostListInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MouStatsOverview extends BaseWidget
{
    public ?int $mouId = null;

    protected function getStats(): array
    {
        $totalCostListMou = CostListMou::where('mou_id', $this->mouId)->sum('amount');
        $totalCostListInvoice = CostListInvoice::where('mou_id', $this->mouId)
            ->whereNotNull('invoice_id')
            ->sum('amount');
        $difference = $totalCostListMou - $totalCostListInvoice;

        return [
            Stat::make('Total Cost List MoU', 'Rp ' . number_format($totalCostListMou, 0, ',', '.'))
                ->description('Total anggaran dalam MoU')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Total Cost List Invoice', 'Rp ' . number_format($totalCostListInvoice, 0, ',', '.'))
                ->description('Total yang sudah ditagihkan')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('success'),

            Stat::make('Selisih', 'Rp ' . number_format(abs($difference), 0, ',', '.'))
                ->description($difference >= 0 ? 'Sisa anggaran' : 'Melebihi anggaran')
                ->descriptionIcon($difference >= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($difference >= 0 ? 'warning' : 'danger'),
        ];
    }
}
