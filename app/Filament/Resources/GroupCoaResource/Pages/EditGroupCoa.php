<?php

namespace App\Filament\Resources\GroupCoaResource\Pages;

use App\Filament\Resources\GroupCoaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGroupCoa extends EditRecord
{
    protected static string $resource = GroupCoaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
