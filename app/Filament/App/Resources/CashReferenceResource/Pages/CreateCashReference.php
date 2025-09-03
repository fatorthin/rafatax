<?php

namespace App\Filament\App\Resources\CashReferenceResource\Pages;

use App\Filament\App\Resources\CashReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCashReference extends CreateRecord
{
    protected static string $resource = CashReferenceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Kas Berhasil Dibuat')
            ->body('Data kas telah berhasil disimpan.');
    }
}

