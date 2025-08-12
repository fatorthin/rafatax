<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionReferenceResource\Pages;
use App\Filament\Resources\PositionReferenceResource\RelationManagers;
use App\Models\PositionReference;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PositionReferenceResource extends Resource
{
    protected static ?string $model = PositionReference::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Referensi';

    protected static ?string $navigationLabel = 'Referensi Jabatan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignorable: fn(?PositionReference $record) => $record),
                Forms\Components\Textarea::make('description')
                    ->nullable()
                    ->maxLength(1000),
                Forms\Components\TextInput::make('salary')
                    ->label('Tunjangan Jabatan')
                    ->numeric()
                    ->default(0),   
            ])->columns([
                'sm' => 1,
                'md' => 2,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->sortable(),   
                Tables\Columns\TextColumn::make('salary')
                    ->label('Tunjangan Jabatan')
                    ->numeric()
                    ->alignEnd()
                    ->formatStateUsing(fn($record) => number_format($record->salary, 0, ',', '.'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListPositionReferences::route('/'),
            'create' => Pages\CreatePositionReference::route('/create'),
            'edit' => Pages\EditPositionReference::route('/{record}/edit'),
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
