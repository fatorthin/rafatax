<?php

namespace App\Filament\App\Resources\MouResource\Pages;

use App\Filament\App\Resources\MouResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditMou extends EditRecord
{
    protected static string $resource = MouResource::class;

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
            ->title('MoU Berhasil Diupdate')
            ->body('Data MoU telah berhasil diperbarui.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure required fields have default values
        $data['status'] = $data['status'] ?? 'unapproved';
        $data['type'] = $data['type'] ?? 'pt';
        $data['percentage_restitution'] = $data['percentage_restitution'] ?? 0;

        return $data;
    }
}

