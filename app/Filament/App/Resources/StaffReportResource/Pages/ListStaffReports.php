<?php

namespace App\Filament\App\Resources\StaffReportResource\Pages;

use App\Filament\App\Resources\StaffReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffReports extends ListRecords
{
    protected static string $resource = StaffReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
