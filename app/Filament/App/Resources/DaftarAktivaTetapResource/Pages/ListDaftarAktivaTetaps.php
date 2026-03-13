<?php

namespace App\Filament\App\Resources\DaftarAktivaTetapResource\Pages;

use App\Filament\App\Resources\DaftarAktivaTetapResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDaftarAktivaTetaps extends ListRecords
{
    protected static string $resource = DaftarAktivaTetapResource::class;

    protected static ?string $title = 'Daftar Aktiva Tetap';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...DaftarAktivaTetapResource::getHeaderActions(),
        ];
    }
}
