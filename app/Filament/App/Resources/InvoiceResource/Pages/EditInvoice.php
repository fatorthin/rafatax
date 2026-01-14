<?php

namespace App\Filament\App\Resources\InvoiceResource\Pages;

use App\Filament\App\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\URL;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;



    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => URL::previous()),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('viewCostList', ['record' => $this->record->id]);
    }
}
