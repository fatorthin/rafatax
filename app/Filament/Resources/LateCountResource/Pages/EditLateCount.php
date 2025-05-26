<?php

namespace App\Filament\Resources\LateCountResource\Pages;

use App\Filament\Resources\LateCountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLateCount extends EditRecord
{
    protected static string $resource = LateCountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
