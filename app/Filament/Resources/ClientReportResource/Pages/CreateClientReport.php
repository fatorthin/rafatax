<?php

namespace App\Filament\Resources\ClientReportResource\Pages;

use App\Filament\Resources\ClientReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClientReport extends CreateRecord
{
    protected static string $resource = ClientReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
