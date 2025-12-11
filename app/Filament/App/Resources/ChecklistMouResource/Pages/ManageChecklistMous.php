<?php

namespace App\Filament\App\Resources\ChecklistMouResource\Pages;

use App\Filament\App\Resources\ChecklistMouResource;
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
