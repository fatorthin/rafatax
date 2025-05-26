<?php

namespace App\Filament\Resources\PerformanceReviewReferenceResource\Pages;

use App\Filament\Resources\PerformanceReviewReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPerformanceReviewReferences extends ListRecords
{
    protected static string $resource = PerformanceReviewReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
