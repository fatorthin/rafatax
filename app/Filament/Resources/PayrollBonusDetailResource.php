<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollBonusDetailResource\Pages;
use App\Filament\Resources\PayrollBonusDetailResource\RelationManagers;
use App\Models\PayrollBonusDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollBonusDetailResource extends Resource
{
    protected static ?string $model = PayrollBonusDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Histori Data';

    protected static ?string $navigationLabel = 'Histori Payroll Bonus';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\Select::make('payroll_bonus_id')
                //     ->relationship('payrollBonus', 'description')
                //     ->label('Periode Payroll Bonus')
                //     ->searchable()
                //     ->preload()
                //     ->required(),
                // Forms\Components\Select::make('staff_id')
                //     ->relationship('staff', 'name', modifyQueryUsing: fn(Builder $query) => $query->where('is_active', true))
                //     ->searchable()
                //     ->preload()
                //     ->required(),
                // Forms\Components\TextInput::make('amount')
                //     ->numeric()
                //     ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payrollBonus.description')->label('Payroll Bonus')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('staff.name')->label('Staff')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('amount')->formatStateUsing(fn($state) => 'Rp. ' . number_format($state, 0, ',', '.'))->alignEnd()->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePayrollBonusDetails::route('/'),

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
