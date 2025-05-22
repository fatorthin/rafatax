<?php

namespace App\Filament\Resources\CategoryMouResource\Pages;

use App\Filament\Resources\CategoryMouResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCategoryMou extends CreateRecord
{
    protected static string $resource = CategoryMouResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
