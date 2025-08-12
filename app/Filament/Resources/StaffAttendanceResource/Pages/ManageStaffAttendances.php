<?php

namespace App\Filament\Resources\StaffAttendanceResource\Pages;

use App\Filament\Resources\StaffAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStaffAttendances extends ManageRecords
{
    protected static string $resource = StaffAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
