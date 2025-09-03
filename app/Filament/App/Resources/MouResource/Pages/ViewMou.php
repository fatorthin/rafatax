<?php

namespace App\Filament\App\Resources\MouResource\Pages;

use App\Filament\App\Resources\MouResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMou extends ViewRecord
{
    protected static string $resource = MouResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->icon('heroicon-o-trash'),
        ];
    }
}

