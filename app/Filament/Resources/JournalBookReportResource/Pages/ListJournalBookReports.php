<?php

namespace App\Filament\Resources\JournalBookReportResource\Pages;

use App\Filament\Resources\JournalBookReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJournalBookReports extends ListRecords
{
    protected static string $resource = JournalBookReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
