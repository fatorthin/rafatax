<?php

namespace App\Filament\App\Resources\MouResource\Pages;

use App\Filament\App\Resources\MouResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMous extends ManageRecords
{
    protected static string $resource = MouResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah MoU Baru')
                ->icon('heroicon-o-plus')
                ->modalWidth('2xl'),
        ];
    }
}
