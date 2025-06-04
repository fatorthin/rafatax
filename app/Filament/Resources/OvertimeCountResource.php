<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OvertimeCountResource\Pages;
use App\Filament\Resources\OvertimeCountResource\RelationManagers;
use App\Models\OvertimeCount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OvertimeCountResource extends Resource
{
    protected static ?string $model = OvertimeCount::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Daftar Lembur Staf';
    protected static ?string $navigationGroup = 'Bagian HRD';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('staff_id')
                    ->label('Staf')
                    ->relationship('staff', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\DatePicker::make('overtime_date')
                    ->label('Tanggal Lembur')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('overtime_count')
                    ->label('Jumlah Lembur (jam)')
                    ->numeric()
                    ->required()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Nama Staf')
                    ->searchable(),
                Tables\Columns\TextColumn::make('overtime_date')
                    ->date()
                    ->label('Tanggal Lembur')
                    ->dateTime('M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('overtime_count')
                    ->label('Jumlah Lembur (jam)')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\ToggleColumn::make('is_verified')
                    ->label('Verifikasi')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->onColor('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Dibuat Pada')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('staff_id')
                    ->label('Staf')
                    ->relationship('staff', 'name')
                    ->searchable(),
                Tables\Filters\Filter::make('overtime_month')
                    ->label('Month')
                    ->form([
                        Forms\Components\Select::make('month')
                            ->label('Month')
                            ->options([
                                '1' => 'January',
                                '2' => 'February',
                                '3' => 'March',
                                '4' => 'April',
                                '5' => 'May',
                                '6' => 'June',
                                '7' => 'July',
                                '8' => 'August',
                                '9' => 'September',
                                '10' => 'October',
                                '11' => 'November',
                                '12' => 'December',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['month'],
                                fn(Builder $query, $month): Builder => $query->whereMonth('overtime_date', $month)
                            );
                    }),
                Tables\Filters\Filter::make('overtime_year')
                    ->label('Year')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = [];
                                $currentYear = now()->year;
                                for ($i = $currentYear - 5; $i <= $currentYear; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year'],
                                fn(Builder $query, $year): Builder => $query->whereYear('overtime_date', $year)
                            );
                    }),
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
            'index' => Pages\ListOvertimeCounts::route('/'),
            'create' => Pages\CreateOvertimeCount::route('/create'),
            'edit' => Pages\EditOvertimeCount::route('/{record}/edit'),
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
