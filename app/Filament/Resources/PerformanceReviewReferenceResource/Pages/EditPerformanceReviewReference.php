<?php

namespace App\Filament\Resources\PerformanceReviewReferenceResource\Pages;

use App\Filament\Resources\PerformanceReviewReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPerformanceReviewReference extends EditRecord
{
    protected static string $resource = PerformanceReviewReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
