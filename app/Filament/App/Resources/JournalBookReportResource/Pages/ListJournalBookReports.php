<?php

namespace App\Filament\App\Resources\JournalBookReportResource\Pages;

use App\Filament\App\Resources\JournalBookReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJournalBookReports extends ListRecords
{
    protected static string $resource = JournalBookReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Buku Jurnal')
                ->icon('heroicon-o-plus')
                ->visible(fn() => static::getResource()::canCreate()),
        ];
    }
}
