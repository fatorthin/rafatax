<?php

namespace App\Filament\App\Resources\CashReportResource\Pages;

use App\Filament\App\Resources\CashReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashReports extends ListRecords
{
    protected static string $resource = CashReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Laporan Kas')
                ->icon('heroicon-o-plus')
                ->visible(fn() => static::getResource()::canCreate()),
        ];
    }
}
