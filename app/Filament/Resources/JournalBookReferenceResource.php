<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalBookReferenceResource\Pages;
use App\Filament\Resources\JournalBookReferenceResource\RelationManagers;
use App\Models\JournalBookReference;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JournalBookReferenceResource extends Resource
{
    protected static ?string $model = JournalBookReference::class;
    protected static ?string $navigationGroup = 'Bagian Keuangan';
    protected static ?string $navigationLabel = 'Daftar Buku Jurnal';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label('Nama Referensi Buku Jurnal'),
                Forms\Components\Textarea::make('description')
                    ->nullable()
                    ->maxLength(500)
                    ->label('Deskripsi'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Referensi Buku Jurnal')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Dibuat Pada')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Diperbarui Pada')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view')
                    ->label('View Detail')
                    ->url(fn(JournalBookReference $record): string => JournalBookReferenceResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                Tables\Actions\Action::make('viewMonthlyDetail')
                    ->label('View Monthly Detail')
                    ->url(fn(JournalBookReference $record): string => JournalBookReferenceResource::getUrl('viewMonthly', ['record' => $record]))
                    ->icon('heroicon-o-eye')
                    ->color('danger'),
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
            'index' => Pages\ListJournalBookReferences::route('/'),
            'create' => Pages\CreateJournalBookReference::route('/create'),
            'edit' => Pages\EditJournalBookReference::route('/{record}/edit'),
            'view' => Pages\ViewJournalBookDetail::route('/{record}/detail'),
            'viewMonthly' => Pages\ViewJournalBookMonthly::route('/{record}/monthly'),
            'monthDetail' => Pages\ViewJournalBookMonthDetail::route('/{record}/month-transactions'),
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
