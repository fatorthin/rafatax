<?php

namespace App\Filament\Resources\MouResource\Widgets;

use App\Models\CostListMou;
use App\Models\CostListInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MouListStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCostListMou = CostListMou::whereHas('mou', function ($query) {
            $query->where('status', 'approved');
        })->sum('total_amount');
        $totalCostListInvoiceUnpaid = CostListInvoice::whereHas('invoice', function ($query) {
            $query->where('invoice_status', 'unpaid');
        })->sum('amount');

        $totalCostListInvoicePaid = CostListInvoice::whereHas('invoice', function ($query) {
            $query->where('invoice_status', 'paid');
        })->sum('amount');

        $totalDiscount = \App\Models\MoU::where('status', 'approved')->sum('discount_amount');
        
        $difference = $totalCostListMou - $totalCostListInvoicePaid - $totalDiscount;

        return [
            Stat::make('Total Cost List MoU', 'Rp ' . number_format($totalCostListMou, 0, ',', '.'))
                ->description('Total anggaran semua MoU (Approved)')
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

            Stat::make('Total Sisa Piutang', 'Rp ' . number_format(max(0, $difference), 0, ',', '.'))
                ->description('Sisa piutang keseluruhan (Total MoU - Paid - Discount)')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger'),
        ];
    }
}
