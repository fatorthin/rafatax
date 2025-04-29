<?php

namespace App\Filament\Resources\IncomeStatementResource\Pages;

use App\Filament\Resources\IncomeStatementResource;
use App\Models\CashReport;
use App\Models\Coa;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MonthlyIncomeStatement extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = IncomeStatementResource::class;

    protected static string $view = 'filament.resources.income-statement-resource.pages.monthly-income-statement';
    
    public $year;
    
    public function mount($year): void
    {
        $this->year = $year;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => route('filament.admin.resources.income-statements.index'))
                ->button(),
        ];
    }
    
    public function table(Table $table): Table
    {
        $months = range(1, 12);
        
        $columns = [
            TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('coa_code')
                ->label('CoA Code')
                ->sortable()
                ->weight('bold'),
            TextColumn::make('coa_name')
                ->label('CoA Name')
                ->sortable()
                ->weight('bold'),
        ];
        
        // Add columns for each month with custom formatting
        foreach ($months as $month) {
            $columns[] = TextColumn::make("month_{$month}")
                ->label(date('F', mktime(0, 0, 0, $month, 1)))
                ->alignEnd()
                ->formatStateUsing(function ($state) {
                    return number_format((float) $state, 0, ',', '.');
                });
        }
        
        // Create a query to get all CoA data with monthly amounts
        $coaQuery = Coa::query()
            ->selectRaw('
                coa.id as id,
                coa.code as coa_code,
                coa.name as coa_name,
                ' . collect($months)->map(function ($month) {
                    return "
                    COALESCE(SUM(CASE 
                        WHEN MONTH(cash_reports.transaction_date) = {$month} 
                        THEN (COALESCE(cash_reports.debit_amount, 0) - COALESCE(cash_reports.credit_amount, 0))
                        ELSE 0 
                    END), 0) as month_{$month}";
                })->join(',') . '
            ')
            ->leftJoin('cash_reports', function ($join) {
                $join->on('coa.id', '=', 'cash_reports.coa_id')
                    ->whereYear('cash_reports.transaction_date', $this->year);
            })
            ->groupBy('coa.id', 'coa.code', 'coa.name')
            ->orderBy('coa.code');
            
        // Create a query for the totals row
        $totalQuery = DB::query()
            ->selectRaw('
                9999999 as id,
                "TOTAL" as coa_code,
                "" as coa_name,
                ' . collect($months)->map(function ($month) {
                    return "
                    COALESCE((
                        SELECT SUM(CASE 
                            WHEN MONTH(transaction_date) = {$month} 
                            THEN (COALESCE(debit_amount, 0) - COALESCE(credit_amount, 0))
                            ELSE 0 
                        END)
                        FROM cash_reports
                        WHERE YEAR(transaction_date) = {$this->year}
                    ), 0) as month_{$month}";
                })->join(',') . '
            ');
            
        // Union the two queries
        $finalQuery = $coaQuery->unionAll($totalQuery);
        
        return $table
            ->query($finalQuery)
            ->columns($columns)
            ->striped()
            ->paginated(false);
    }
    
    public function getTableRecordKey(mixed $record): string
    {
        return (string) $record->id;
    }
    
    public function getTitle(): string
    {
        return "Monthly Income Statement - {$this->year}";
    }
} 