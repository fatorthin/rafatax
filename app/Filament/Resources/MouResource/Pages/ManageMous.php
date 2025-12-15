<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Filament\Resources\MouResource;
use App\Filament\Resources\MouResource\Widgets\MouListStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMous extends ManageRecords
{
    protected static string $resource = MouResource::class;

    protected static ?string $title = 'Kelola Daftar MoU';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New MoU')
                ->icon('heroicon-o-plus')
                ->modalHeading('Add New MoU')
                ->modalWidth('7xl'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MouListStatsOverview::class,
        ];
    }
}
