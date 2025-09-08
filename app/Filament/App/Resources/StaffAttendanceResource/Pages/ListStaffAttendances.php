<?php

namespace App\Filament\App\Resources\StaffAttendanceResource\Pages;

use App\Filament\App\Resources\StaffAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffAttendances extends ListRecords
{
    protected static string $resource = StaffAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('view-attendance-monthly')
                ->label('Laporan Presensi Bulanan')
                ->icon('heroicon-o-calendar')
                ->color('success')
                ->url(fn (): string => static::getResource()::getUrl('view-attendance-monthly')),
        ];
    }
}
