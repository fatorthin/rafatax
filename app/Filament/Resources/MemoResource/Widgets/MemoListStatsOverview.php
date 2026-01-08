<?php

namespace App\Filament\Resources\MemoResource\Widgets;

use App\Models\Memo;
use App\Models\CostListInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MemoListStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // 1. Total Fee from all Memos
        $totalFee = Memo::sum('total_fee');

        // 2. Total Invoiced Amount (linked to Memos)
        // We filter CostListInvoice where the related invoice has a memo_id
        $totalInvoiced = CostListInvoice::whereHas('invoice', function ($query) {
            $query->whereNotNull('memo_id');
        })->sum('amount');

        // 3. Difference
        $difference = $totalFee - $totalInvoiced;

        return [
            Stat::make('Total Fee Memo', 'Rp ' . number_format($totalFee, 0, ',', '.'))
                ->description('Total nilai jasa semua Memo')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Total Invoiced', 'Rp ' . number_format($totalInvoiced, 0, ',', '.'))
                ->description('Total yang sudah ditagihkan (Invoice)')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('success'),

            Stat::make('Selisih', 'Rp ' . number_format(abs($difference), 0, ',', '.'))
                ->description($difference >= 0 ? 'Belum ditagihkan' : 'Melebihi nilai memo')
                ->descriptionIcon($difference >= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($difference >= 0 ? 'warning' : 'danger'),
        ];
    }
}
