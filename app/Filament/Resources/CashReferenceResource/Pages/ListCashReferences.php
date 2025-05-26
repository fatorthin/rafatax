<?php

namespace App\Filament\Resources\CashReferenceResource\Pages;

use App\Filament\Resources\CashReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashReferences extends ListRecords
{
    protected static string $resource = CashReferenceResource::class;

    protected static ?string $title = 'Daftar Kas';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
