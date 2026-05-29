<?php

namespace App\Filament\Resources\MouPiutangLamaResource\Pages;

use App\Filament\Resources\MouPiutangLamaResource;
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
}
