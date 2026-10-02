<?php

namespace App\Filament\App\Resources\JournalBookReferenceResource\Pages;

use App\Filament\App\Resources\JournalBookReferenceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalBookReference extends CreateRecord
{
    protected static string $resource = JournalBookReferenceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
