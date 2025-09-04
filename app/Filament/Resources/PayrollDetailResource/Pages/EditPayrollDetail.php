<?php

namespace App\Filament\Resources\PayrollDetailResource\Pages;

use App\Filament\Resources\PayrollDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayrollDetail extends EditRecord
{
    protected static string $resource = PayrollDetailResource::class;

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
