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
        $currentYear = date('Y');

        $mouCondition = function ($query) use ($currentYear) {
            $query->where('status', 'approved')
                ->where(function ($q) use ($currentYear) {
                    $q->whereYear('approved_date', $currentYear)
                        ->orWhere(function ($sq) use ($currentYear) {
                            $sq->whereNull('approved_date')
                                ->whereYear('created_at', $currentYear);
                        });
                })
                ->where(function ($q) {
                    $q->where('mou_piutang_lama', false)
                        ->orWhereNull('mou_piutang_lama');
                });
        };

        $totalCostListMou = CostListMou::whereHas('mou', $mouCondition)->sum('total_amount');

        $totalCostListInvoiceUnpaid = CostListInvoice::whereHas('invoice', function ($query) {
            $query->whereIn('invoice_status', ['unpaid', 'overdue'])
                ->whereHas('mou', function ($q) {
                    $q->where('mou_piutang_lama', false)
                        ->orWhereNull('mou_piutang_lama');
                });
        })->sum('amount');

        $totalCostListInvoicePaid = CostListInvoice::whereHas('invoice', function ($query) use ($mouCondition) {
            $query->where('invoice_status', 'paid')
                ->whereHas('mou', $mouCondition);
        })->sum('amount');

        $totalCostListInvoicePaidAll = CostListInvoice::whereHas('invoice', function ($query) {
            $query->where('invoice_status', 'paid')
                ->whereHas('mou', function ($q) {
                    $q->where('mou_piutang_lama', false)
                        ->orWhereNull('mou_piutang_lama');
                });
        })->sum('amount');

        $totalDiscount = \App\Models\MoU::where('status', 'approved')
            ->where(function ($q) use ($currentYear) {
                $q->whereYear('approved_date', $currentYear)
                    ->orWhere(function ($sq) use ($currentYear) {
                        $sq->whereNull('approved_date')
                            ->whereYear('created_at', $currentYear);
                    });
            })
            ->where(function ($q) {
                $q->where('mou_piutang_lama', false)
                    ->orWhereNull('mou_piutang_lama');
            })
            ->sum('discount_amount');
        
        $difference = $totalCostListMou - $totalCostListInvoicePaid - $totalDiscount;

        return [
            Stat::make("Total Cost List MoU ({$currentYear})", 'Rp ' . number_format($totalCostListMou, 0, ',', '.'))
                ->description("Total anggaran MoU approved tahun {$currentYear}")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Total Invoice Unpaid', 'Rp ' . number_format($totalCostListInvoiceUnpaid, 0, ',', '.'))
                ->description('Total tagihan belum lunas (Unpaid + Overdue)')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make("Total Invoice Paid ({$currentYear})", 'Rp ' . number_format($totalCostListInvoicePaid, 0, ',', '.'))
                ->description('Terbayar MoU ' . $currentYear . ' (Semua tahun: Rp ' . number_format($totalCostListInvoicePaidAll, 0, ',', '.') . ')')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make("Total Sisa Piutang ({$currentYear})", 'Rp ' . number_format(max(0, $difference), 0, ',', '.'))
                ->description("Sisa komitmen MoU {$currentYear} (Total MoU - Paid - Diskon)")
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger'),
        ];
    }
}
