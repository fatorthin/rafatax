<?php

namespace App\Filament\Resources\PerformanceReviewReferenceResource\Pages;

use App\Filament\Resources\PerformanceReviewReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePerformanceReviewReference extends CreateRecord
{
    protected static string $resource = PerformanceReviewReferenceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
