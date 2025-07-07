<?php

namespace App\Filament\Resources\JournalBookReportResource\Pages;

use App\Filament\Resources\JournalBookReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalBookReport extends CreateRecord
{
    protected static string $resource = JournalBookReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
