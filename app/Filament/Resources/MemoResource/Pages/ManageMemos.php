<?php

namespace App\Filament\Resources\MemoResource\Pages;

use Filament\Actions;
use App\Filament\Resources\MemoResource;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Resources\MemoResource\Widgets\MemoListStatsOverview;

class ManageMemos extends ManageRecords
{
    protected static string $resource = MemoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MemoListStatsOverview::class,
        ];
    }
}
