<?php

namespace App\Filament\App\Resources\CashReportResource\Pages;

use App\Filament\App\Resources\CashReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCashReport extends CreateRecord
{
    protected static string $resource = CashReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Laporan Kas Berhasil Dibuat')
            ->body('Data laporan kas telah berhasil disimpan.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values
        $data['type'] = 'manual';
        $data['invoice_id'] = $data['invoice_id'] ?? '0';
        $data['mou_id'] = $data['mou_id'] ?? '0';
        $data['cost_list_invoice_id'] = $data['cost_list_invoice_id'] ?? '0';

        return $data;
    }
}
