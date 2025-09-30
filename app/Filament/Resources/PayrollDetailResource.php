<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Staff;
use App\Models\Payroll;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PayrollDetail;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PayrollDetailResource\Pages;
use App\Filament\Resources\PayrollDetailResource\RelationManagers;
use Filament\Forms\Get;
use Filament\Forms\Set;

class PayrollDetailResource extends Resource
{
    protected static ?string $model = PayrollDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Histori Payroll';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('payroll_id')
                    ->label('Payroll')
                    ->options(Payroll::all()->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('staff_id')
                    ->label('Staff')
                    ->options(Staff::all()->pluck('name', 'id'))
                    ->required(),
                Forms\Components\TextInput::make('salary')
                    ->label('Salary')
                    ->prefix('Rp')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('bonus_position')
                    ->label('Bonus Position')
                    ->prefix('Rp')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('bonus_competency')
                    ->label('Bonus Competency')
                    ->prefix('Rp')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('overtime_count')
                    ->label('Overtime Count')
                    ->numeric()
                    ->suffix('Jam')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('visit_solo_count')
                    ->label('Visit Solo Count')
                    ->numeric()
                    ->suffix('Kali')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('visit_luar_solo_count')
                    ->label('Visit Luar Solo Count')
                    ->numeric()
                    ->suffix('Kali')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('sick_leave_count')
                    ->label('Sick Leave Count')
                    ->numeric()
                    ->suffix('Kali')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('halfday_count')
                    ->label('Halfday Count')
                    ->numeric()
                    ->suffix('Kali')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('leave_count')
                    ->label('Leave Count')
                    ->numeric()
                    ->suffix('Kali')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('bonus_lain')
                    ->label('Bonus Lain')
                    ->numeric()
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('cut_bpjs_kesehatan')
                    ->label('Cut BPJS Kesehatan')
                    ->numeric()
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('cut_bpjs_ketenagakerjaan')
                    ->label('Cut BPJS Ketenagakerjaan')
                    ->numeric()
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('cut_lain')
                    ->label('Cut Lain')
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->required(),
                Forms\Components\TextInput::make('cut_hutang')
                    ->label('Cut Hutang')
                    ->numeric()
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    })
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('total_salary_display')
                    ->label('Total Salary (otomatis)')
                    ->prefix('Rp')
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Set $set, Get $get) {
                        $set('total_salary_display', self::formatCurrency((int) round(self::computeTotalSalary($get))));
                    }),
            ]);
    }

    private static function computeTotalSalary(Get $get): float
    {
        $salary = (int) ($get('salary') ?? 0);
        $bonusPosition = (int) ($get('bonus_position') ?? 0);
        $bonusCompetency = (int) ($get('bonus_competency') ?? 0);
        $overtimeCount = (int) ($get('overtime_count') ?? 0);
        $visitSoloCount = (int) ($get('visit_solo_count') ?? 0);
        $visitLuarSoloCount = (int) ($get('visit_luar_solo_count') ?? 0);
        $bonusLain = (int) ($get('bonus_lain') ?? 0);
        $cutBpjsKes = (int) ($get('cut_bpjs_kesehatan') ?? 0);
        $cutBpjsKetenaga = (int) ($get('cut_bpjs_ketenagakerjaan') ?? 0);
        $cutLain = (int) ($get('cut_lain') ?? 0);
        $cutHutang = (int) ($get('cut_hutang') ?? 0);
        $sickLeave = (int) ($get('sick_leave_count') ?? 0);
        $halfday = (int) ($get('halfday_count') ?? 0);
        $leave = (int) ($get('leave_count') ?? 0);

        $bonusOvertime = $overtimeCount * 10000;
        $bonusVisitSolo = $visitSoloCount * 10000;
        $bonusVisitLuarSolo = $visitLuarSoloCount * 15000;

        $cutSakit = $sickLeave * 0.5 * ($salary / 25);
        $cutTengahHari = $halfday * 0.5 * ($salary / 25);
        $cutIjin = $leave * ($salary / 25);

        $total = $salary
            + $bonusPosition
            + $bonusCompetency
            + $bonusOvertime
            + $bonusVisitSolo
            + $bonusVisitLuarSolo
            + $bonusLain
            - $cutBpjsKes
            - $cutBpjsKetenaga
            - $cutLain
            - $cutHutang
            - $cutSakit
            - $cutTengahHari
            - $cutIjin;

        return $total;
    }

    private static function formatCurrency(int $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payroll.name')
                    ->label('Periode Payroll')
                    ->sortable(),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Nama Staff')
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
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPayrollDetails::route('/'),
            'create' => Pages\CreatePayrollDetail::route('/create'),
            'edit' => Pages\EditPayrollDetail::route('/{record}/edit'),
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
