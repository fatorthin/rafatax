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
            Actions\Action::make('syncKpi')
                ->label('Sinkronisasi Data')
                ->icon('heroicon-m-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Sinkronisasi')
                ->modalDescription('Apakah Anda yakin ingin melakukan sinkronisasi data CaseProject dari KPI?')
                ->modalSubmitActionLabel('Ya, Sinkronisasi')
                ->modalCancelActionLabel('Batal')
                ->action(function () {
                    $service = app(KpiApiService::class);

                    $username = config('services.kpi.username');
                    $password = config('services.kpi.password');

                    if (!$username || !$password) {
                        Notification::make()
                            ->title('Konfigurasi tidak lengkap')
                            ->body('Username/password KPI belum dikonfigurasi di file .env')
                            ->warning()
                            ->send();
                        return;
                    }

                    $token = $service->authenticate($username, $password);
                    if (!$token) {
                        Notification::make()
                            ->title('Gagal login ke KPI')
                            ->body('Periksa username/password atau konfigurasi API. ' . ($service->getLastError() ? ('Detail: ' . $service->getLastError()) : ''))
                            ->danger()
                            ->send();
                        return;
                    }

                    $items = $service->fetchCaseProjects($token);
                    if (empty($items)) {
                        Notification::make()
                            ->title('Tidak ada data untuk disinkronkan')
                            ->info()
                            ->send();
                        return;
                    }

                    $summary = $service->sync($items);

                    Notification::make()
                        ->title('Sinkronisasi selesai')
                        ->body("Dibuat: {$summary['created']}, Diperbarui: {$summary['updated']}, Dilewati: {$summary['skipped']}")
                        ->success()
                        ->send();
                }),

                Actions\CreateAction::make()
                ->label('Add New Case Project')
                ->icon('heroicon-o-plus')
                ->modalHeading('Add New Case Project')
                ->modalWidth('2xl'),
        ];
    }
}
