<?php

namespace App\Filament\App\Widgets;

use App\Models\CashReference;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashReferenceOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCashReferences = CashReference::count();
        $cashReferencesWithTransactions = CashReference::has('cashReports')->count();
        $totalBalance = CashReference::with('cashReports')->get()->sum(function ($cashRef) {
            $debitTotal = $cashRef->cashReports->sum('debit_amount');
            $creditTotal = $cashRef->cashReports->sum('credit_amount');
            return $debitTotal - $creditTotal;
        });

        return [
            Stat::make('Total Kas', $totalCashReferences)
                ->description('Jumlah total kas yang tersedia')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Kas Aktif', $cashReferencesWithTransactions)
                ->description('Kas yang memiliki transaksi')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Saldo', 'Rp ' . number_format($totalBalance, 0, ',', '.'))
                ->description('Total saldo dari semua kas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}

