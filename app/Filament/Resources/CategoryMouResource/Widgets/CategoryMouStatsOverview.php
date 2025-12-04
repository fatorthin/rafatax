<?php

namespace App\Filament\Resources\CategoryMouResource\Widgets;

use App\Models\MoU;
use App\Models\CostListMou;
use App\Models\CostListInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CategoryMouStatsOverview extends BaseWidget
{
    /**
     * @var int|null
     */
    public ?int $categoryId = null;

    protected function getStats(): array
    {
        if ($this->categoryId) {
            $mouIds = MoU::where('category_mou_id', $this->categoryId)->pluck('id')->toArray();

            $totalCostListMou = CostListMou::whereIn('mou_id', $mouIds)->sum('amount');
            $totalCostListInvoice = CostListInvoice::whereIn('mou_id', $mouIds)
                ->whereNotNull('invoice_id')
                ->sum('amount');
        } else {
            $totalCostListMou = CostListMou::sum('amount');
            $totalCostListInvoice = CostListInvoice::whereNotNull('invoice_id')->sum('amount');
        }

        $difference = $totalCostListMou - $totalCostListInvoice;

        return [
            Stat::make('Total Nominal MoU', 'Rp ' . number_format($totalCostListMou, 0, ',', '.'))
                ->description('Total anggaran dalam kategori MoU')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Total Nominal Invoice', 'Rp ' . number_format($totalCostListInvoice, 0, ',', '.'))
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
