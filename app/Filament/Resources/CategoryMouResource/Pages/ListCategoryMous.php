<?php

namespace App\Filament\Resources\CategoryMouResource\Pages;

use App\Filament\Resources\CategoryMouResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoryMous extends ListRecords
{
    protected static string $resource = CategoryMouResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
