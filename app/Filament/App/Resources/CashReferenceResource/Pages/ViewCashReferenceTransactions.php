<?php

namespace App\Filament\App\Resources\CashReferenceResource\Pages;

use App\Filament\App\Resources\CashReferenceResource;
use App\Models\CashReference;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewCashReferenceTransactions extends Page
{
    protected static string $resource = CashReferenceResource::class;

    protected static string $view = 'filament.app.resources.cash-reference-resource.pages.view-cash-reference-transactions';

    public ?CashReference $record = null;

    public function mount(int|string $record): void
    {
        $this->record = CashReference::findOrFail($record);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->record->cashReports()->getQuery())
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'debit' => 'Debit',
                        'credit' => 'Credit',
                        'manual' => 'Manual',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'debit' => 'success',
                        'credit' => 'danger',
                        'manual' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'debit' => 'Debit',
                        'credit' => 'Credit',
                        'manual' => 'Manual',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('backToList')
                ->label('Kembali ke Daftar')
                ->icon('heroicon-o-arrow-left')
                ->url(fn(): string => $this->getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Widget untuk summary transaksi
        ];
    }
}

