<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages;
use App\Filament\Resources\PayrollResource\RelationManagers;
use App\Models\Payroll;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Daftar Payroll';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Fieldset::make('Periode Payroll')
                    ->schema([
                        Forms\Components\Select::make('payroll_month')
                            ->label('Bulan')
                            ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F')])->toArray())
                            ->required()
                            ->dehydrated(false)
                            ->reactive()
                            ->afterStateHydrated(function ($state, \Filament\Forms\Set $set, ?\App\Models\Payroll $record) {
                                if ($record && $record->payroll_date) {
                                    $set('payroll_month', (int) \Carbon\Carbon::parse($record->payroll_date)->format('m'));
                                } elseif (empty($state)) {
                                    $set('payroll_month', (int) now()->month);
                                }
                            })
                            ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                                $year = (int) ($get('payroll_year') ?: now()->year);
                                $month = (int) $state;
                                $set('payroll_date', sprintf('%04d-%02d-01', $year, $month));
                            }),
                        Forms\Components\Select::make('payroll_year')
                            ->label('Tahun')
                            ->options(collect(range((int) now()->year, (int) now()->year - 5))->mapWithKeys(fn ($y) => [$y => (string) $y])->toArray())
                            ->required()
                            ->dehydrated(false)
                            ->reactive()
                            ->afterStateHydrated(function ($state, \Filament\Forms\Set $set, ?\App\Models\Payroll $record) {
                                if ($record && $record->payroll_date) {
                                    $set('payroll_year', (int) \Carbon\Carbon::parse($record->payroll_date)->format('Y'));
                                } elseif (empty($state)) {
                                    $set('payroll_year', (int) now()->year);
                                }
                            })
                            ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                                $month = (int) ($get('payroll_month') ?: now()->month);
                                $year = (int) $state;
                                $set('payroll_date', sprintf('%04d-%02d-01', $year, $month));
                            }),
                        Forms\Components\Hidden::make('payroll_date')
                            ->required()
                            ->dehydrated(true)
                            ->afterStateHydrated(function ($state, \Filament\Forms\Set $set, ?\App\Models\Payroll $record, \Filament\Forms\Get $get) {
                                if ($record && $record->payroll_date) {
                                    $set('payroll_date', \Carbon\Carbon::parse($record->payroll_date)->format('Y-m-01'));
                                    return;
                                }
                                $year = (int) ($get('payroll_year') ?: now()->year);
                                $month = (int) ($get('payroll_month') ?: now()->month);
                                $set('payroll_date', sprintf('%04d-%02d-01', $year, $month));
                            }),
                    ])
                    ->columns(2),
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
                    ->url(fn ($record) => static::getUrl('detail', ['record' => $record]))
                    ->color('primary'),
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
            'index' => Pages\ManagePayrolls::route('/'),
            'detail' => Pages\DetailPayroll::route('/{record}/detail'),
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
