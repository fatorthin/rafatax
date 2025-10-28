<?php

namespace App\Filament\App\Resources\PayrollDetailResource\Pages;

use App\Filament\App\Resources\PayrollDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePayrollDetails extends ManageRecords
{
    protected static string $resource = PayrollDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
