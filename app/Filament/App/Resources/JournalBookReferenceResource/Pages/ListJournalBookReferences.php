<?php

namespace App\Filament\App\Resources\JournalBookReferenceResource\Pages;

use App\Filament\App\Resources\JournalBookReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJournalBookReferences extends ListRecords
{
    protected static string $resource = JournalBookReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Buku Jurnal')
                ->icon('heroicon-o-plus')
                ->visible(fn() => static::getResource()::canCreate()),
        ];
    }
}
