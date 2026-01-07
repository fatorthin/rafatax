<?php

namespace App\Filament\Resources\MemoResource\Pages;

use App\Filament\Resources\MemoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMemos extends ManageRecords
{
    protected static string $resource = MemoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
        ];
    }
}
