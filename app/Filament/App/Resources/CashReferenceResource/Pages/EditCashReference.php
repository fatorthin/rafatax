<?php

namespace App\Filament\App\Resources\CashReferenceResource\Pages;

use App\Filament\App\Resources\CashReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCashReference extends EditRecord
{
    protected static string $resource = CashReferenceResource::class;

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
            ->title('Kas Berhasil Diupdate')
            ->body('Data kas telah berhasil diperbarui.');
    }
}

