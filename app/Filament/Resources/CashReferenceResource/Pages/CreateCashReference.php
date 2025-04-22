<?php

namespace App\Filament\Resources\CashReferenceResource\Pages;

use App\Filament\Resources\CashReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCashReference extends CreateRecord
{
    protected static string $resource = CashReferenceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
