<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Exports\ClientExporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('exportFiltered')
                ->label('Export Data (Filtered)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action(function () {
                    try {
                        // Get filtered clients based on current table state
                        $query = $this->getFilteredTableQuery();
                        $clients = $query->with('staff')->get();

                        if ($clients->isEmpty()) {
                            Notification::make()
                                ->title('Data Kosong')
                                ->warning()
                                ->body('Tidak ada data untuk diekspor.')
                                ->send();
                            return;
                        }

                        // Export to Excel
                        $filename = ClientExporter::export($clients);

                        // Success notification
                        Notification::make()
                            ->title('Export Berhasil')
                            ->success()
                            ->body('Data klien terfilter berhasil diekspor (' . $clients->count() . ' data).')
                            ->send();

                        // Download file
                        return response()->download(
                            storage_path('app/public/' . $filename),
                            $filename
                        )->deleteFileAfterSend(true);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Export Gagal')
                            ->danger()
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->send();
                    }
                }),
            Actions\Action::make('export')
                ->label('Export Semua Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    try {
                        // Get all clients with staff relationship
                        $clients = \App\Models\Client::with('staff')->get();

                        // Export to Excel
                        $filename = ClientExporter::export($clients);

                        // Success notification
                        Notification::make()
                            ->title('Export Berhasil')
                            ->success()
                            ->body('Semua data klien berhasil diekspor (' . $clients->count() . ' data).')
                            ->send();

                        // Download file
                        return response()->download(
                            storage_path('app/public/' . $filename),
                            $filename
                        )->deleteFileAfterSend(true);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Export Gagal')
                            ->danger()
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
