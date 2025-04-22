<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use App\Models\CashReport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCashReports extends ListRecords
{
    protected static string $resource = CashReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    public function getFooter(): ?View
    {
        $debitTotal = CashReport::sum('debit_amount');
        $creditTotal = CashReport::sum('credit_amount');
        $balance = $debitTotal - $creditTotal;
        
        return view('filament.resources.cash-report-resource.pages.cash-report-footer', [
            'debitTotal' => $debitTotal,
            'creditTotal' => $creditTotal,
            'balance' => $balance,
        ]);
    }
}
