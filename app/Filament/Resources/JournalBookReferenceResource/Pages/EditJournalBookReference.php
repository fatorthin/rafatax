<?php

namespace App\Filament\Resources\JournalBookReferenceResource\Pages;

use App\Filament\Resources\JournalBookReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJournalBookReference extends EditRecord
{
    protected static string $resource = JournalBookReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
