<?php

namespace App\Filament\Resources\PayrollBonusDetailResource\Pages;

use App\Filament\Resources\PayrollBonusDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePayrollBonusDetails extends ManageRecords
{
    protected static string $resource = PayrollBonusDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
