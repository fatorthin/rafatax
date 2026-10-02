<?php

namespace App\Filament\App\Resources\CoaResource\Pages;

use App\Filament\App\Resources\CoaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCoa extends CreateRecord
{
    protected static string $resource = CoaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
