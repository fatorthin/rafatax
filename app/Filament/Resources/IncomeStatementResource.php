<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomeStatementResource\Pages;
use App\Models\CashReport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IncomeStatementResource extends Resource
{
    protected static ?string $model = CashReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    
    protected static ?string $navigationGroup = 'Reports';
    
    protected static ?string $navigationLabel = 'Income Statement';
    
    protected static ?string $modelLabel = 'Income Statement';
    
    protected static ?string $pluralModelLabel = 'Income Statements';

    public static function getNavigationBadge(): ?string
    {
        return static::$model::selectRaw('COUNT(DISTINCT YEAR(transaction_date)) as year_count')
            ->first()
            ->year_count;
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->query(
                CashReport::query()
                    ->selectRaw('YEAR(transaction_date) as year, MIN(id) as id')
                    ->groupBy('year')
                    ->orderBy('year', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->size('lg')
                    ->alignment('center')
                    ->color('primary')
                    ->weight('bold'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_monthly')
                    ->label('View Monthly Report')
                    ->url(fn ($record) => route('filament.admin.resources.income-statements.monthly', ['year' => $record->year]))
                    ->icon('heroicon-o-chart-bar')
                    ->color('success')
                    ->button(),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomeStatements::route('/'),
            'monthly' => Pages\MonthlyIncomeStatement::route('/{year}/monthly'),
        ];
    }
} 