<?php

namespace App\Filament\Resources\CashReferenceResource\Pages;

use App\Filament\Resources\CashReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashReference extends EditRecord
{
    protected static string $resource = CashReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
