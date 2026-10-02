<?php

namespace App\Filament\App\Resources\JournalBookReferenceResource\Pages;

use App\Filament\App\Resources\JournalBookReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJournalBookReference extends EditRecord
{
    protected static string $resource = JournalBookReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn($record) => static::getResource()::canDelete($record)),
            Actions\ForceDeleteAction::make()
                ->visible(fn($record) => static::getResource()::canForceDelete($record)),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
