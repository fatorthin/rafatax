<?php

namespace App\Filament\App\Resources\StaffCompetencyResource\Pages;

use App\Filament\App\Resources\StaffCompetencyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffCompetency extends EditRecord
{
    protected static string $resource = StaffCompetencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
