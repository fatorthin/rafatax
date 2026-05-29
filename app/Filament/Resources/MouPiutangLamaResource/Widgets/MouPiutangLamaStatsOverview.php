<?php

namespace App\Filament\Resources\MouPiutangLamaResource\Widgets;

use App\Models\MoU;
use App\Models\CostListMou;
use App\Models\CostListInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MouPiutangLamaStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Only include MoU where mou_piutang_lama is true
        $mouIds = MoU::whereNotNull('mou_piutang_lama')
            ->where('mou_piutang_lama', true)
            ->pluck('id');

        $totalNominalMou = CostListMou::whereIn('mou_id', $mouIds)
            ->sum('total_amount');

        $totalInvoicePaid = CostListInvoice::whereHas('invoice', function ($query) use ($mouIds) {
            $query->whereIn('mou_id', $mouIds)
                ->where('invoice_status', 'paid');
        })->sum('amount');

        $totalInvoiceUnpaid = CostListInvoice::whereHas('invoice', function ($query) use ($mouIds) {
            $query->whereIn('mou_id', $mouIds)
                ->where('invoice_status', 'unpaid');
        })->sum('amount');

        $selisih = $totalNominalMou - $totalInvoicePaid - $totalInvoiceUnpaid;

        return [
            Stat::make('Total Nominal MoU', 'Rp ' . number_format($totalNominalMou, 0, ',', '.'))
                ->description('Total anggaran semua MoU Piutang Lama')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Total Invoice Unpaid', 'Rp ' . number_format($totalInvoiceUnpaid, 0, ',', '.'))
                ->description('Total tagihan yang belum dibayar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Invoice Paid', 'Rp ' . number_format($totalInvoicePaid, 0, ',', '.'))
                ->description('Total tagihan yang sudah dibayar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Selisih / Sisa Piutang', 'Rp ' . number_format(abs($selisih), 0, ',', '.'))
                ->description($selisih >= 0 ? 'Sisa piutang yang belum tertagih' : 'Tagihan melebihi nilai MoU')
                ->descriptionIcon($selisih >= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($selisih >= 0 ? 'danger' : 'gray'),
        ];
    }
}
