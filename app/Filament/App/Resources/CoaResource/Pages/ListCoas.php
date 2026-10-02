<?php

namespace App\Filament\App\Resources\CoaResource\Pages;

use App\Filament\App\Resources\CoaResource;
use App\Filament\Exports\CoaExporter;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCoas extends ListRecords
{
    protected static string $resource = CoaResource::class;

    public function getTitle(): string
    {
        return 'Referensi COA';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah COA')
                ->visible(fn() => static::getResource()::canCreate()),
            Actions\Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    try {
                        $coas = \App\Models\Coa::with('groupCoa')->get();
                        $filename = CoaExporter::export($coas);

                        Notification::make()
                            ->title('Export Berhasil')
                            ->success()
                            ->body('Data COA berhasil diekspor (' . $coas->count() . ' data).')
                            ->send();

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
