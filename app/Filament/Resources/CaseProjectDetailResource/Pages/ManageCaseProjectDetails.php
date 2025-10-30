<?php

namespace App\Filament\Resources\CaseProjectDetailResource\Pages;

use App\Filament\Resources\CaseProjectDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCaseProjectDetails extends ManageRecords
{
    protected static string $resource = CaseProjectDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
