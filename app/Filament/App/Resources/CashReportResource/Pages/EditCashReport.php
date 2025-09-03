<?php

namespace App\Filament\App\Resources\CashReportResource\Pages;

use App\Filament\App\Resources\CashReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCashReport extends EditRecord
{
    protected static string $resource = CashReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Laporan Kas Berhasil Diupdate')
            ->body('Data laporan kas telah berhasil diperbarui.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure required fields have default values
        $data['type'] = $data['type'] ?? 'manual';
        $data['invoice_id'] = $data['invoice_id'] ?? '0';
        $data['mou_id'] = $data['mou_id'] ?? '0';
        $data['cost_list_invoice_id'] = $data['cost_list_invoice_id'] ?? '0';

        return $data;
    }
}
