<?php

namespace App\Filament\App\Resources\CaseProjectResource\Pages;

use App\Filament\App\Resources\CaseProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCaseProjects extends ManageRecords
{
    protected static string $resource = CaseProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data')
                ->icon('heroicon-o-plus')
                ->mutateFormDataUsing(function (array $data): array {
                    if (($data['client_input_type'] ?? 'database') === 'manual') {
                        $data['client_id'] = null;
                    } else {
                        $data['client_name'] = null;
                    }
                    unset($data['client_input_type']);
                    return $data;
                }),
        ];
    }
}
