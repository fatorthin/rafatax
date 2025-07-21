<?php

namespace App\Filament\Resources\DepresiasiAktivaTetapResource\Pages;

use App\Filament\Resources\DepresiasiAktivaTetapResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDepresiasiAktivaTetap extends CreateRecord
{
    protected static string $resource = DepresiasiAktivaTetapResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
