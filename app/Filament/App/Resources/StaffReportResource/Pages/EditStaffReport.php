<?php

namespace App\Filament\App\Resources\StaffReportResource\Pages;

use App\Filament\App\Resources\StaffReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffReport extends EditRecord
{
    protected static string $resource = StaffReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
