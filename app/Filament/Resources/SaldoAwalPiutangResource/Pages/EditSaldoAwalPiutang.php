<?php

namespace App\Filament\Resources\SaldoAwalPiutangResource\Pages;

use App\Filament\Resources\SaldoAwalPiutangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSaldoAwalPiutang extends EditRecord
{
    protected static string $resource = SaldoAwalPiutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
