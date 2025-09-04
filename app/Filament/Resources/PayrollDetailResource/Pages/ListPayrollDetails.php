<?php

namespace App\Filament\Resources\PayrollDetailResource\Pages;

use App\Filament\Resources\PayrollDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayrollDetails extends ListRecords
{
    protected static string $resource = PayrollDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
