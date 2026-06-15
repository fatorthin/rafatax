<?php

namespace App\Filament\App\Resources\MouPiutangLamaResource\Pages;

use App\Filament\App\Resources\MouPiutangLamaResource;
use App\Filament\App\Resources\MouPiutangLamaResource\Widgets\MouPiutangLamaStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMouPiutangLamas extends ManageRecords
{
    protected static string $resource = MouPiutangLamaResource::class;

    protected static ?string $title = 'MoU Piutang Lama';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah MoU Piutang Lama')
                ->icon('heroicon-o-plus')
                ->modalWidth('7xl')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['mou_piutang_lama'] = true;
                    return $data;
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MouPiutangLamaStatsOverview::class,
        ];
    }
}

