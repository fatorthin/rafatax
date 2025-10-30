<?php

namespace App\Filament\Resources\PayrollBonusResource\Pages;

use App\Filament\Resources\PayrollBonusResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePayrollBonuses extends ManageRecords
{
    protected static string $resource = PayrollBonusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
