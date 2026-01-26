<?php

namespace App\Filament\Resources\CoaResource\Pages;

use App\Filament\Resources\CoaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCoas extends ListRecords
{
    protected static string $resource = CoaResource::class;

    public function getTitle(): string
    {
        return 'COA References';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Add New COA'),
            Actions\Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    try {
                        $coas = \App\Models\Coa::with('groupCoa')->get();
                        $filename = \App\Filament\Exports\CoaExporter::export($coas);

                        \Filament\Notifications\Notification::make()
                            ->title('Export Berhasil')
                            ->success()
                            ->body('Data COA berhasil diekspor (' . $coas->count() . ' data).')
                            ->send();

                        return response()->download(
                            storage_path('app/public/' . $filename),
                            $filename
                        )->deleteFileAfterSend(true);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Export Gagal')
                            ->danger()
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
