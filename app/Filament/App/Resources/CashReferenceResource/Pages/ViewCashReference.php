<?php

namespace App\Filament\App\Resources\CashReferenceResource\Pages;

use App\Filament\App\Resources\CashReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCashReference extends ViewRecord
{
    protected static string $resource = CashReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->icon('heroicon-o-trash'),
            Actions\Action::make('viewTransactions')
                ->label('Lihat Transaksi')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->url(fn(): string => route('filament.app.resources.cash-references.transactions', ['record' => $this->record])),
        ];
    }
}

