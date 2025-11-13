<?php

namespace App\Filament\Resources\DepresiasiAktivaTetapResource\Pages;

use App\Filament\Resources\DepresiasiAktivaTetapResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDepresiasiAktivaTetaps extends ManageRecords
{
    protected static string $resource = DepresiasiAktivaTetapResource::class;

    protected static ?string $title = 'Histori Depresiasi Aktiva Tetap';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Depresiasi Aktiva Tetap')
                ->modalHeading('Tambah Depresiasi Aktiva Tetap')
                ->modalWidth('xl'),
        ];
    }
}
