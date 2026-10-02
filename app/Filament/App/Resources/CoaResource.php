<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CoaResource\Pages;
use App\Models\Coa;
use App\Models\GroupCoa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CoaResource extends Resource
{
    protected static ?string $model = Coa::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Referensi COA';
    protected static ?string $modelLabel = 'COA';
    protected static ?string $pluralModelLabel = 'Referensi COA';
    protected static ?int $navigationSort = 16;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('cash-manager')
            || $user->hasPermission('coa.view')
            || $user->hasPermission('coas.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('cash-manager')
            || $user->hasPermission('coa.create');
    }

    public static function canEdit(Model $record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('cash-manager')
            || $user->hasPermission('coa.edit');
    }

    public static function canDelete(Model $record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('cash-manager')
            || $user->hasPermission('coa.delete');
    }

    public static function canForceDelete(Model $record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('cash-manager')
            || $user->hasPermission('coa.delete');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->label('Kode COA'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nama COA'),
                Forms\Components\Select::make('type')
                    ->options([
                        'kkp' => 'KKP',
                        'pt' => 'PT'
                    ])
                    ->required()
                    ->label('Tipe'),
                Forms\Components\Select::make('group_coa_id')
                    ->label('Group COA')
                    ->options(GroupCoa::all()->pluck('name', 'id'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('groupCoa.name')
                    ->label('Group COA')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'kkp' => 'KKP',
                        'pt' => 'PT'
                    ]),
                Tables\Filters\SelectFilter::make('group_coa_id')
                    ->label('Group COA')
                    ->options(GroupCoa::all()->pluck('name', 'id'))
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoas::route('/'),
            'create' => Pages\CreateCoa::route('/create'),
            'edit' => Pages\EditCoa::route('/{record}/edit'),
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
