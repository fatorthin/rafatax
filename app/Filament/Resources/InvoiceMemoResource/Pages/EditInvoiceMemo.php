<?php

namespace App\Filament\Resources\InvoiceMemoResource\Pages;

use App\Filament\Resources\InvoiceMemoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\URL;

class EditInvoiceMemo extends EditRecord
{
    protected static string $resource = InvoiceMemoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => URL::previous()),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
