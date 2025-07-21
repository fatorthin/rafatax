<?php

namespace App\Filament\Resources\DaftarAktivaTetapResource\Pages;

use App\Filament\Resources\DaftarAktivaTetapResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDaftarAktivaTetap extends CreateRecord
{
    protected static string $resource = DaftarAktivaTetapResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
