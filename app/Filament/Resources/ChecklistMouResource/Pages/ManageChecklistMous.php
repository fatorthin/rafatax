<?php

namespace App\Filament\Resources\ChecklistMouResource\Pages;

use App\Filament\Resources\ChecklistMouResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageChecklistMous extends ManageRecords
{
    protected static string $resource = ChecklistMouResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
