<?php

namespace App\Filament\App\Widgets;

use App\Models\CashReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashReportOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $monthlyDebit = CashReport::whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('debit_amount');

        $monthlyCredit = CashReport::whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('credit_amount');

        $monthlyBalance = $monthlyDebit - $monthlyCredit;

        return [
            Stat::make('Total Debit Bulan Ini', 'Rp ' . number_format($monthlyDebit, 2, ',', '.'))
                ->description('Total debit bulan ' . now()->format('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Credit Bulan Ini', 'Rp ' . number_format($monthlyCredit, 2, ',', '.'))
                ->description('Total credit bulan ' . now()->format('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Saldo Bulan Ini', 'Rp ' . number_format($monthlyBalance, 2, ',', '.'))
                ->description('Saldo bulan ' . now()->format('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($monthlyBalance >= 0 ? 'success' : 'danger'),
        ];
    }
}
