<?php

namespace App\Filament\Resources\JournalBookReferenceResource\Pages;

use App\Filament\Resources\JournalBookReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalBookReference extends CreateRecord
{
    protected static string $resource = JournalBookReferenceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
