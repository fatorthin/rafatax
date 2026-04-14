<?php

namespace App\Filament\Resources\CaseProjectResource\Pages;

use App\Filament\Exports\CaseProjectExporter;
use App\Filament\Resources\CaseProjectResource;
use App\Services\KpiApiService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageCaseProjects extends ManageRecords
{
    protected static string $resource = CaseProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Select::make('case_type')
                        ->label('Filter Kategori')
                        ->placeholder('Semua Kategori')
                        ->options([
                            'SPT'         => 'SPT',
                            'SP2DK'       => 'SP2DK',
                            'Pembetulan'  => 'PEMBETULAN',
                            'Pemeriksaan' => 'PEMERIKSAAN',
                            'Himbauan'    => 'HIMBAUAN',
                            'Lainnya'     => 'LAINNYA',
                        ]),
                    Select::make('status')
                        ->label('Filter Status')
                        ->placeholder('Semua Status')
                        ->options([
                            'open'        => 'OPEN',
                            'in_progress' => 'IN PROGRESS',
                            'done'        => 'DONE',
                            'paid'        => 'PAID',
                        ]),
                ])
                ->modalHeading('Export Data Proyek Kasus')
                ->modalSubmitActionLabel('Export')
                ->action(function (array $data) {
                    $caseType = $data['case_type'] ?? null;
                    $status   = $data['status'] ?? null;
                    $filename = CaseProjectExporter::export($caseType, $status);
                    $filepath = storage_path('app/public/' . $filename);

                    return response()->download($filepath, $filename)->deleteFileAfterSend(true);
                }),
            Actions\CreateAction::make()
                ->label('Add New Case Project')
                ->icon('heroicon-o-plus')
                ->modalHeading('Add New Case Project')
                ->modalWidth('2xl'),
        ];
    }
}
