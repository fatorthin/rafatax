<?php

namespace App\Filament\Resources\InvoiceMemoResource\Pages;

use App\Filament\Resources\InvoiceMemoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoiceMemo extends CreateRecord
{
    protected static string $resource = InvoiceMemoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['mou_id'] = null;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
