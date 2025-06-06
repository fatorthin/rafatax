<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Filament\Resources\MouResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMou extends CreateRecord
{
    protected static string $resource = MouResource::class;

    protected static ?string $title = 'Tambah MoU';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
