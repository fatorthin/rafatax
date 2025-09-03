<?php

namespace App\Filament\App\Resources\MouResource\Pages;

use App\Filament\App\Resources\MouResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateMou extends CreateRecord
{
    protected static string $resource = MouResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('MoU Berhasil Dibuat')
            ->body('Data MoU telah berhasil disimpan.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values
        $data['status'] = $data['status'] ?? 'unapproved';
        $data['type'] = $data['type'] ?? 'pt';
        $data['percentage_restitution'] = $data['percentage_restitution'] ?? 0;

        return $data;
    }
}

