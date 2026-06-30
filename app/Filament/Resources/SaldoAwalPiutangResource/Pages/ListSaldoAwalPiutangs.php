<?php

namespace App\Filament\Resources\SaldoAwalPiutangResource\Pages;

use App\Filament\Resources\SaldoAwalPiutangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSaldoAwalPiutangs extends ListRecords
{
    protected static string $resource = SaldoAwalPiutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
