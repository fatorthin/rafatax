<?php

namespace App\Filament\App\Resources\CashReportResource\Pages;

use App\Filament\App\Resources\CashReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCashReport extends ViewRecord
{
    protected static string $resource = CashReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->icon('heroicon-o-trash'),
        ];
    }
}
