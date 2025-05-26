<?php

namespace App\Filament\Resources\LateCountResource\Pages;

use App\Filament\Resources\LateCountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLateCount extends CreateRecord
{
    protected static string $resource = LateCountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
