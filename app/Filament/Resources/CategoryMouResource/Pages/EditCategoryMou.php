<?php

namespace App\Filament\Resources\CategoryMouResource\Pages;

use App\Filament\Resources\CategoryMouResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoryMou extends EditRecord
{
    protected static string $resource = CategoryMouResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
