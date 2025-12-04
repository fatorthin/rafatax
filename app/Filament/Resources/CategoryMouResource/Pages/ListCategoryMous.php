<?php

namespace App\Filament\Resources\CategoryMouResource\Pages;

use App\Filament\Resources\CategoryMouResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\CategoryMouResource\Widgets\CategoryMouStatsOverview;

class ListCategoryMous extends ListRecords
{
    protected static string $resource = CategoryMouResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        // Show aggregated totals across all categories (no specific categoryId passed)
        return [
            CategoryMouStatsOverview::make([
                'categoryId' => null,
            ]),
        ];
    }
}
