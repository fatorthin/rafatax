<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PayrollDetailResource\Pages;
use App\Filament\App\Resources\PayrollDetailResource\RelationManagers;
use App\Models\PayrollDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\HasPermissions;

class PayrollDetailResource extends Resource
{
    use HasPermissions;
    protected static ?string $model = PayrollDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'HRD';

    protected static ?string $navigationLabel = 'Histori Payroll';

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
                Tables\Columns\TextColumn::make('payroll.name')
                    ->label('Periode Payroll')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Nama Staff')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salary')
                    ->label('Gaji Pokok')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonus_position')
                    ->label('TUNJAB')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonus_competency')
                    ->label('TUNKOMP')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sick_leave_count')
                    ->label('Sakit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('halfday_count')
                    ->label('Tengah Hari')
                    ->sortable(),
                Tables\Columns\TextColumn::make('leave_count')
                    ->label('Ijin')
                    ->sortable(),
                Tables\Columns\TextColumn::make('overtime_count')
                    ->label('Lembur')
                    ->sortable(),
                Tables\Columns\TextColumn::make('visit_solo_count')
                    ->label('T. Solo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('visit_luar_solo_count')
                    ->label('T. Luar Solo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonus_lembur')
                    ->label('Bonus Lembur')
                    ->getStateUsing(function ($record) {
                        return $record->overtime_count * 10000;
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonus_visit_solo')
                    ->label('Bonus Visit Solo')
                    ->getStateUsing(function ($record) {
                        return $record->visit_solo_count * 10000;
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonus_visit_luar_solo')
                    ->label('Bonus Visit Luar Solo')
                    ->getStateUsing(function ($record) {
                        return $record->visit_luar_solo_count * 10000;
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonus_lain')
                    ->label('Bonus Lain')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cut_bpjs_kesehatan')
                    ->label('Cut BPJS Kesehatan')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cut_bpjs_ketenagakerjaan')
                    ->label('Cut BPJS Ketenagakerjaan')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cut_sakit')
                    ->label('Cut Sakit')
                    ->getStateUsing(function ($record) {
                        return $record->sick_leave_count * 0.5 * $record->salary / 25;
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cut_tengah_hari')
                    ->label('Cut Tengah Hari')
                    ->getStateUsing(function ($record) {
                        return $record->halfday_count * 0.5 * $record->salary / 25;
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cut_ijin')
                    ->label('Cut Ijin')
                    ->getStateUsing(function ($record) {
                        return $record->leave_count * $record->salary / 25;
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cut_lain')
                    ->label('Cut Lain')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cut_hutang')
                    ->label('Cut Hutang')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_bonus')
                    ->label('Total Bonus')
                    ->getStateUsing(function ($record) {
                        return ($record->overtime_count * 10000) + ($record->visit_solo_count * 10000) + ($record->visit_luar_solo_count * 15000) + $record->bonus_lain;
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cut')
                    ->label('Total Cut')
                    ->getStateUsing(function ($record) {
                        return $record->cut_bpjs_kesehatan + $record->cut_bpjs_ketenagakerjaan + $record->cut_lain + $record->cut_hutang + ($record->sick_leave_count * 0.5 * $record->salary / 25) + ($record->halfday_count * 0.5 * $record->salary / 25) + ($record->leave_count * $record->salary / 25);
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_salary')
                    ->label('Total Salary')
                    ->getStateUsing(function ($record) {
                        return $record->salary + $record->bonus_position + $record->bonus_competency + ($record->overtime_count * 10000) + ($record->visit_solo_count * 10000) + ($record->visit_luar_solo_count * 15000) + $record->bonus_lain - $record->cut_bpjs_kesehatan - $record->cut_bpjs_ketenagakerjaan - $record->cut_lain - $record->cut_hutang - ($record->sick_leave_count * 0.5 * $record->salary / 25) - ($record->halfday_count * 0.5 * $record->salary / 25) - ($record->leave_count * $record->salary / 25);
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->sortable(),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
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
            'index' => Pages\ManagePayrollDetails::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->whereHas('payroll', function (Builder $query) {
                $query->whereNull('deleted_at');
            });
    }
}
