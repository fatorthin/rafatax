<?php

namespace App\Filament\App\Resources\StaffResource\Pages;

use App\Filament\App\Resources\StaffResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStaff extends ManageRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
