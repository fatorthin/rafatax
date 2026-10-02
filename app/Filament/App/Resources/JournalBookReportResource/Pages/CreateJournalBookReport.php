<?php

namespace App\Filament\App\Resources\JournalBookReportResource\Pages;

use App\Filament\App\Resources\JournalBookReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalBookReport extends CreateRecord
{
    protected static string $resource = JournalBookReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
