<?php

namespace App\Filament\Resources\PositionReferenceResource\Pages;

use App\Filament\Resources\PositionReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPositionReferences extends ListRecords
{
    protected static string $resource = PositionReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
