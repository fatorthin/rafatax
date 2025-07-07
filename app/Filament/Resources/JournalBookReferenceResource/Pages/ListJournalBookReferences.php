<?php

namespace App\Filament\Resources\JournalBookReferenceResource\Pages;

use App\Filament\Resources\JournalBookReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJournalBookReferences extends ListRecords
{
    protected static string $resource = JournalBookReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
