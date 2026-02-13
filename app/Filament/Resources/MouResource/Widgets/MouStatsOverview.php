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
        $totalCostListMou = CostListMou::where('mou_id', $this->mouId)->sum('total_amount');

        $totalCostListInvoiceUnpaid = CostListInvoice::where('mou_id', $this->mouId)
            ->whereNotNull('invoice_id')
            ->whereHas('invoice', function ($query) {
                $query->where('invoice_status', 'unpaid');
            })
            ->sum('amount');

        $totalCostListInvoicePaid = CostListInvoice::where('mou_id', $this->mouId)
            ->whereNotNull('invoice_id')
            ->whereHas('invoice', function ($query) {
                $query->where('invoice_status', 'paid');
            })
            ->sum('amount');

        $difference = $totalCostListMou - ($totalCostListInvoiceUnpaid + $totalCostListInvoicePaid);

        return [
            Stat::make('Total Cost List MoU', 'Rp ' . number_format($totalCostListMou, 0, ',', '.'))
                ->description('Total anggaran dalam MoU')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Total Invoice Unpaid', 'Rp ' . number_format($totalCostListInvoiceUnpaid, 0, ',', '.'))
                ->description('Total tagihan yang belum dibayar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Invoice Paid', 'Rp ' . number_format($totalCostListInvoicePaid, 0, ',', '.'))
                ->description('Total tagihan yang sudah dibayar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Selisih', 'Rp ' . number_format(abs($difference), 0, ',', '.'))
                ->description($difference >= 0 ? 'Sisa anggaran' : 'Melebihi anggaran')
                ->descriptionIcon($difference >= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($difference >= 0 ? 'warning' : 'danger'),
        ];
    }
}
