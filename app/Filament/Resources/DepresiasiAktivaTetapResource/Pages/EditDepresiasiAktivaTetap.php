<?php

namespace App\Filament\Resources\DepresiasiAktivaTetapResource\Pages;

use App\Filament\Resources\DepresiasiAktivaTetapResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepresiasiAktivaTetap extends EditRecord
{
    protected static string $resource = DepresiasiAktivaTetapResource::class;

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
