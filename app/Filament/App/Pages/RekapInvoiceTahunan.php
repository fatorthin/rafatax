<?php

namespace App\Filament\App\Pages;

use App\Filament\Pages\RekapInvoiceTahunan as BasePage;
use App\Filament\App\Resources\InvoiceResource;
use Filament\Tables\Table;
use Filament\Tables;

class RekapInvoiceTahunan extends BasePage
{
    protected static ?string $slug = 'rekap-invoice-tahunan';
    protected static bool $shouldRegisterNavigation = false;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->actions([
                Tables\Actions\Action::make('view_list')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn($record): string => InvoiceResource::getUrl('index', [
                        'tableFilters' => [
                            'date_range' => [
                                'year' => $record->year,
                            ],
                        ],
                    ])),
            ]);
    }
}
