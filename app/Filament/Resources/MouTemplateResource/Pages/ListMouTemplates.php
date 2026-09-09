<?php

namespace App\Filament\Resources\MouTemplateResource\Pages;

use App\Filament\Resources\MouTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMouTemplates extends ListRecords
{
    protected static string $resource = MouTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
