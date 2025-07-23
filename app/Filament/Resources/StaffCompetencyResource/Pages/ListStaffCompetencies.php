<?php

namespace App\Filament\Resources\StaffCompetencyResource\Pages;

use App\Filament\Resources\StaffCompetencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffCompetencies extends ListRecords
{
    protected static string $resource = StaffCompetencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
