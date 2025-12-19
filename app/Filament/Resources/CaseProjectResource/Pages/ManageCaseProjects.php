<?php

namespace App\Filament\Resources\CaseProjectResource\Pages;

use App\Filament\Resources\CaseProjectResource;
use App\Services\KpiApiService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCaseProjects extends ManageRecords
{
    protected static string $resource = CaseProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New Case Project')
                ->icon('heroicon-o-plus')
                ->modalHeading('Add New Case Project')
                ->modalWidth('2xl'),
        ];
    }
}
