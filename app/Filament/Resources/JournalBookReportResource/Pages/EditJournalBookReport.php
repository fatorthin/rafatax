<?php

namespace App\Filament\Resources\JournalBookReportResource\Pages;

use App\Filament\Resources\JournalBookReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJournalBookReport extends EditRecord
{
    protected static string $resource = JournalBookReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
