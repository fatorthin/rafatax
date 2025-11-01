<?php

namespace App\Filament\Resources;

use App\Models\Coa;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\JournalBookReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\JournalBookReportResource\Pages;
use App\Filament\Resources\JournalBookReportResource\RelationManagers;

class JournalBookReportResource extends Resource
{
    protected static ?string $model = JournalBookReport::class;
    protected static ?string $navigationGroup = 'Histori Data';
    protected static ?string $navigationLabel = 'Histori Buku Jurnal';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('description')
                    ->nullable()
                    ->maxLength(500)
                    ->label('Deskripsi'),
                Forms\Components\Select::make('journal_book_id')
                    ->relationship('journal_book', 'name')
                    ->label('Buku Jurnal')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make(name: 'coa_id')
                    ->label('CoA')
                    ->options(function () {
                        return Coa::all()->mapWithKeys(function ($coa) {
                            return [$coa->id => $coa->code . ' - ' . $coa->name];
                        });
                    })
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('debit_amount')
                    ->numeric()
                    ->required()
                    ->label('Debit')
                    ->default(0),
                Forms\Components\TextInput::make('credit_amount')
                    ->numeric()
                    ->required()
                    ->label('Kredit')
                    ->default(0),
                Forms\Components\DatePicker::make('transaction_date')
                    ->required()
                    ->label('Tanggal Transaksi'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal Transaksi')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('journal_book.name')
                    ->label('Buku Jurnal')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('coa.name')
                    ->label('CoA')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('debit_amount')
                    ->label('Debit')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 0, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Sum of Debit')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 0, ',', '.');
                            })
                    ),
                Tables\Columns\TextColumn::make('credit_amount')
                    ->label('Kredit')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 0, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Sum of Credit')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 0, ',', '.');
                            })
                    ),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
                Tables\Actions\ForceDeleteAction::make()
                    ->requiresConfirmation(),
                Tables\Actions\RestoreAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListJournalBookReports::route('/'),
            'create' => Pages\CreateJournalBookReport::route('/create'),
            'edit' => Pages\EditJournalBookReport::route('/{record}/edit'),

        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
