<?php

namespace App\Filament\App\Resources\DaftarAktivaTetapResource\Pages;

use App\Filament\App\Resources\DaftarAktivaTetapResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDaftarAktivaTetap extends EditRecord
{
    protected static string $resource = DaftarAktivaTetapResource::class;

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
