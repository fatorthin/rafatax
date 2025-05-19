<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Filament\Resources\MouResource;
use App\Filament\Widgets\MouStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMous extends ListRecords
{
    protected static string $resource = MouResource::class;

    public function getTitle(): string
    {
        return 'List of MoU';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MouStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Add New MoU'),
        ];
    }
}
