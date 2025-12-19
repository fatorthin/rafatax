<?php

namespace App\Filament\App\Resources\CaseProjectResource\Pages;

use App\Filament\App\Resources\CaseProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCaseProjects extends ManageRecords
{
    protected static string $resource = CaseProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
