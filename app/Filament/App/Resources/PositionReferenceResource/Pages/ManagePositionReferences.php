<?php

namespace App\Filament\App\Resources\PositionReferenceResource\Pages;

use App\Filament\App\Resources\PositionReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePositionReferences extends ManageRecords
{
    protected static string $resource = PositionReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
