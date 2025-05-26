<?php

namespace App\Filament\Resources\LateCountResource\Pages;

use App\Filament\Resources\LateCountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLateCounts extends ListRecords
{
    protected static string $resource = LateCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
