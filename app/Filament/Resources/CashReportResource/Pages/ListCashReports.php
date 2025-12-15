<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use App\Models\CashReport;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\View\View;

class ListCashReports extends ManageRecords
{
    protected static string $resource = CashReportResource::class;

    protected static ?string $title = 'Histori Kas';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('neraca_lajur')
                ->label('Neraca Lajur Bulanan (KKP)')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->url(fn() => static::getResource()::getUrl('neraca-lajur'))
                ->button(),
            Actions\Action::make('general_ledger')
                ->label('General Ledger')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('success')
                ->url(fn() => static::getResource()::getUrl('general-ledger'))
                ->button(),
        ];
    }
}
