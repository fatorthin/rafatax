<?php

namespace App\Filament\Resources\OvertimeCountResource\Pages;

use App\Filament\Resources\OvertimeCountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOvertimeCount extends EditRecord
{
    protected static string $resource = OvertimeCountResource::class;

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
