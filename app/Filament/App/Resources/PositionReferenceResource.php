<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PositionReferenceResource\Pages;
use App\Models\PositionReference;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\HasPermissions;

class PositionReferenceResource extends Resource
{

    use HasPermissions;

    protected static ?string $model = PositionReference::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'HRD';

    protected static ?string $navigationLabel = 'Referensi Jabatan';

    /**
     * Control sidebar visibility for this resource based on permissions.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * Guard list page access for non-authorized users.
     */
    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

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
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePositionReferences::route('/'),
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
