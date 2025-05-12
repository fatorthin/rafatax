<?php

namespace App\Filament\Resources\GroupCoaResource\Pages;

use App\Filament\Resources\GroupCoaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGroupCoas extends ListRecords
{
    protected static string $resource = GroupCoaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
