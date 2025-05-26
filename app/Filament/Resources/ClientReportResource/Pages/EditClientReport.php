<?php

namespace App\Filament\Resources\ClientReportResource\Pages;

use App\Filament\Resources\ClientReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientReport extends EditRecord
{
    protected static string $resource = ClientReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
