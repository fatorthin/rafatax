<?php

namespace App\Filament\Resources\OvertimeCountResource\Pages;

use App\Filament\Resources\OvertimeCountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOvertimeCounts extends ListRecords
{
    protected static string $resource = OvertimeCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
