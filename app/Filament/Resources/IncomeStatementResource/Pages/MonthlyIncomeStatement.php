<?php

namespace App\Filament\Resources\IncomeStatementResource\Pages;

use App\Filament\Resources\IncomeStatementResource;
use App\Models\CashReport;
use App\Models\Coa;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MonthlyIncomeStatement extends Page
{
    protected static string $resource = IncomeStatementResource::class;

    protected static string $view = 'filament.resources.income-statement-resource.pages.monthly-income-statement';
    
    public $year;
    public $records;
    public $columns;
    
    public function mount($year): void
    {
        $this->year = $year;
        $this->prepareTableData();
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
    
    protected function prepareTableData(): void
    {
        $months = range(1, 12);
        
        $this->columns = [
            ['name' => 'coa_code', 'label' => 'CoA Code'],
            ['name' => 'coa_name', 'label' => 'CoA Name'],
        ];
        
        foreach ($months as $month) {
            $this->columns[] = [
                'name' => "month_{$month}", 
                'label' => date('F', mktime(0, 0, 0, $month, 1)),
                'align' => 'end'
            ];
        }
        
        $coaQuery = Coa::query()
            ->selectRaw('
                coa.id as id,
                coa.code as coa_code,
                coa.name as coa_name,
                coa.type as coa_type,
                CONCAT(coa.type, "_", "1", "_", coa.code) as sort_key,
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
            ->groupBy('coa.id', 'coa.code', 'coa.name', 'coa.type');
            
        $coaTypes = Coa::distinct('type')->pluck('type')->toArray();
        
        $subtotalQueries = [];
        foreach ($coaTypes as $type) {
            $subtotalQueries[] = DB::query()
                ->selectRaw('
                    9999990 + ' . array_search($type, $coaTypes) . ' as id,
                    "SUBTOTAL" as coa_code,
                    "Total ' . ucfirst($type) . '" as coa_name,
                    "' . $type . '" as coa_type,
                    CONCAT("' . $type . '", "_", "2") as sort_key,
                    ' . collect($months)->map(function ($month) use ($type) {
                        return "
                        COALESCE((
                            SELECT SUM(CASE 
                                WHEN MONTH(transaction_date) = {$month} 
                                THEN (COALESCE(debit_amount, 0) - COALESCE(credit_amount, 0))
                                ELSE 0 
                            END)
                            FROM cash_reports
                            JOIN coa ON cash_reports.coa_id = coa.id
                            WHERE YEAR(transaction_date) = {$this->year}
                            AND coa.type = '{$type}'
                        ), 0) as month_{$month}";
                    })->join(',') . '
                ');
        }
            
        $totalQuery = DB::query()
            ->selectRaw('
                9999999 as id,
                "TOTAL" as coa_code,
                "GRAND TOTAL" as coa_name,
                "total" as coa_type,
                "Z_total" as sort_key,
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
            
        $finalQuery = $coaQuery;
        foreach ($subtotalQueries as $subtotalQuery) {
            $finalQuery = $finalQuery->unionAll($subtotalQuery);
        }
        $finalQuery = $finalQuery->unionAll($totalQuery);
        
        $wrappedQuery = DB::table(DB::raw("({$finalQuery->toSql()}) as income_data"))
            ->mergeBindings($finalQuery->getQuery())
            ->select('*')
            ->orderBy('sort_key');
            
        $this->records = $wrappedQuery->get();
    }
    
    public function getTitle(): string
    {
        return "Monthly Income Statement - {$this->year}";
    }
} 