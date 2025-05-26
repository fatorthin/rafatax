<?php

namespace App\Filament\Resources\OvertimeCountResource\Pages;

use App\Filament\Resources\OvertimeCountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOvertimeCount extends CreateRecord
{
    protected static string $resource = OvertimeCountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
