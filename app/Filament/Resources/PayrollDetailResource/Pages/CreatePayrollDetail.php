<?php

namespace App\Filament\Resources\PayrollDetailResource\Pages;

use App\Filament\Resources\PayrollDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollDetail extends CreateRecord
{
    protected static string $resource = PayrollDetailResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
