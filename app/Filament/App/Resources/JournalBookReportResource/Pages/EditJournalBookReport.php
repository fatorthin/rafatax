<?php

namespace App\Filament\App\Resources\JournalBookReportResource\Pages;

use App\Filament\App\Resources\JournalBookReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJournalBookReport extends EditRecord
{
    protected static string $resource = JournalBookReportResource::class;

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
