<?php

namespace App\Filament\App\Resources\CoaResource\Pages;

use App\Filament\App\Resources\CoaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCoa extends EditRecord
{
    protected static string $resource = CoaResource::class;

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
