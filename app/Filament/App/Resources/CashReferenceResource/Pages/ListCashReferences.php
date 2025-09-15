<?php

namespace App\Filament\App\Resources\CashReferenceResource\Pages;

use App\Filament\App\Resources\CashReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashReferences extends ListRecords
{
    protected static string $resource = CashReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kas')
                ->icon('heroicon-o-plus')
                ->visible(fn() => static::getResource()::canCreate()),
        ];
    }
}
