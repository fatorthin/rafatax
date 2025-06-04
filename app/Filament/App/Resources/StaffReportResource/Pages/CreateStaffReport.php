<?php

namespace App\Filament\App\Resources\StaffReportResource\Pages;

use App\Filament\App\Resources\StaffReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStaffReport extends CreateRecord
{
    protected static string $resource = StaffReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
