<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PayrollResource\Pages;
use App\Filament\App\Resources\PayrollResource\RelationManagers;
use App\Models\Payroll;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\HasPermissions;

class PayrollResource extends Resource
{
    use HasPermissions;
    protected static ?string $model = Payroll::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'HRD';

    protected static ?string $navigationLabel = 'Daftar Payroll';

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
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('payroll_date')
                    ->label('Periode')
                    ->date('F Y'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn($record) => static::getUrl('detail', ['record' => $record]))
                    ->color('primary'),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // Tables\Actions\ForceDeleteAction::make(),
                // Tables\Actions\RestoreAction::make(),
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
            'index' => Pages\ManagePayrolls::route('/'),
            'detail' => Pages\PayrollDetail::route('/{record}/detail'),
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
