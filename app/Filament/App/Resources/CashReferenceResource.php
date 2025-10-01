<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CashReferenceResource\Pages;
use App\Models\CashReference;
use App\Traits\HasPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CashReferenceResource extends Resource
{
    use HasPermissions;
    protected static ?string $model = CashReference::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Daftar Kas';
    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $modelLabel = 'Kas';
    protected static ?string $pluralModelLabel = 'Daftar Kas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kas')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Kas')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama kas'),
                        Forms\Components\TextInput::make('description')
                            ->label('Deskripsi')
                            ->maxLength(500)
                            ->placeholder('Masukkan deskripsi kas (opsional)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kas')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cash_reports_count')
                    ->label('Jumlah Transaksi')
                    ->counts('cashReports')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_balance')
                    ->label('Total Saldo')
                    ->money('IDR')
                    ->getStateUsing(function ($record) {
                        $debitTotal = $record->cashReports()->sum('debit_amount');
                        $creditTotal = $record->cashReports()->sum('credit_amount');
                        $total = $debitTotal - $creditTotal;
                        return 'Rp ' . number_format($total, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('has_transactions')
                    ->label('Memiliki Transaksi')
                    ->options([
                        'yes' => 'Ya',
                        'no' => 'Tidak',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                isset($data['has_transactions']) && $data['has_transactions'] === 'yes',
                                fn(Builder $query): Builder => $query->has('cashReports')
                            )
                            ->when(
                                isset($data['has_transactions']) && $data['has_transactions'] === 'no',
                                fn(Builder $query): Builder => $query->doesntHave('cashReports')
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\Action::make('viewDetail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn(CashReference $record): string => CashReferenceResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('viewMonthlyDetail')
                    ->label('Monthly')
                    ->icon('heroicon-o-calendar')
                    ->color('success')
                    ->url(fn(CashReference $record): string => CashReferenceResource::getUrl('viewMonthly', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Data'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashReferences::route('/'),
            'create' => Pages\CreateCashReference::route('/create'),
            'view' => Pages\ViewCashReferenceDetail::route('/{record}/detail'),
            'viewMonthly' => Pages\ViewCashReferenceMonthly::route('/{record}/monthly'),
            'monthDetail' => Pages\ViewCashReferenceMonthDetail::route('/{record}/month-transactions'),
            'edit' => Pages\EditCashReference::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('cashReports')
            ->latest('created_at');
    }
}
