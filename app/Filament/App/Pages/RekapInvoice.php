<?php

namespace App\Filament\App\Pages;

use App\Filament\Pages\RekapInvoice as BasePage;
use App\Filament\App\Resources\InvoiceResource;
use Filament\Tables\Table;
use Filament\Tables;

class RekapInvoice extends BasePage
{
    protected static ?string $navigationGroup = 'Keuangan';

    // Override table to point to App Resource URL
    public function table(Table $table): Table
    {
        return parent::table($table)
            ->actions([
                Tables\Actions\Action::make('view_list')
                    ->label('Lihat List')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn($record): string => InvoiceResource::getUrl('index', [
                        'tableFilters' => [
                            'invoice_type' => [ // InvoiceResource might use 'type' or 'invoice_type' as filter name.
                                'value' => $record->invoice_type,
                            ],
                        ],
                    ])),
                Tables\Actions\Action::make('view_monthly')
                    ->label('Lihat Bulanan')
                    ->icon('heroicon-o-calendar')
                    ->action(function ($record) {
                        return redirect()->to(RekapInvoiceMonthly::getUrl(['type' => $record->invoice_type]));
                    }),
            ]);
    }
}
