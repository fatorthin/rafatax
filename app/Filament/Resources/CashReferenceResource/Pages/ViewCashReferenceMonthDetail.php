<?php

namespace App\Filament\Resources\CashReferenceResource\Pages;

use Carbon\Carbon;
use App\Models\Coa;
use Filament\Actions;
use App\Models\CashReport;
use Filament\Tables\Table;
use App\Models\CashReference;
use Filament\Resources\Pages\Page;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\CashReferenceResource;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\View;

class ViewCashReferenceMonthDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CashReferenceResource::class;
    
    protected static string $view = 'filament.resources.cash-reference-resource.pages.view-cash-reference-month-detail';
    
    // Record property that will be injected by Filament
    public CashReference $record;
    
    // NO mount() method - let Filament handle it automatically
    
    public function getTitle(): string
    {
        $year = request()->query('year');
        $month = (int) request()->query('month');
        
        $monthName = Carbon::create()->month($month)->format('F');
        
        return "Transactions - {$this->record->name} - {$monthName} {$year}";
    }
    
    // Calculate previous month's ending balance
    protected function getPreviousMonthBalance(): float
    {
        $year = (int) request()->query('year');
        $month = (int) request()->query('month');
        
        // Calculate the previous month and year
        $prevMonth = $month - 1;
        $prevYear = $year;
        
        if ($prevMonth === 0) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        }
        
        // Get the last day of the previous month
        $lastDayPrevMonth = Carbon::create($prevYear, $prevMonth)->endOfMonth()->format('Y-m-d');
        
        // Calculate all transactions up to the end of the previous month
        $prevBalance = CashReport::where('cash_reference_id', $this->record->id)
            ->where('transaction_date', '<=', $lastDayPrevMonth)
            ->sum(DB::raw('debit_amount - credit_amount'));
            
        return $prevBalance;
    }
    
    public function table(Table $table): Table
    {
        $year = (int) request()->query('year');
        $month = (int) request()->query('month');
        
        if (!$year || !$month) {
            return $table->query(CashReport::where('id', 0)); // Empty query if no year/month
        }
        
        // Get monthly transactions
        $query = CashReport::query()
            ->where('cash_reference_id', $this->record->id)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->orderBy('transaction_date')
            ->orderBy('id');
        
        return $table
            ->query($query)
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date('d-M-Y'),
                TextColumn::make('coa.code')
                    ->label('CoA')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('coa.name')
                    ->label('CoA Name')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('debit_amount')
                    ->numeric()
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 0, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 0, ',', '.');
                            })
                    )
                    ->alignEnd(),
                TextColumn::make('credit_amount')
                    ->numeric()
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 0, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 0, ',', '.');
                            })
                    )
                    ->alignEnd(),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 0, ',', '.');
                    })
                    ->getStateUsing(function ($record, $column) {
                        // Get previous month balance
                        $prevBalance = $this->getPreviousMonthBalance();
                        
                        // Get all cash reports for this month up to the current record
                        $year = (int) request()->query('year');
                        $month = (int) request()->query('month');
                        
                        $cashReports = CashReport::where('cash_reference_id', $record->cash_reference_id)
                            ->whereYear('transaction_date', $year)
                            ->whereMonth('transaction_date', $month)
                            ->where(function($q) use ($record) {
                                $q->where('transaction_date', '<', $record->transaction_date)
                                    ->orWhere(function($innerQ) use ($record) {
                                        $innerQ->where('transaction_date', '=', $record->transaction_date)
                                            ->where('id', '<', $record->id);
                                    });
                            })
                            ->orderBy('transaction_date')
                            ->orderBy('id')
                            ->get();
                        
                        // Calculate running balance starting from previous month balance
                        $balance = $prevBalance;
                        
                        // Add all transactions up to current record
                        foreach ($cashReports as $report) {
                            $balance += $report->debit_amount - $report->credit_amount;
                        }
                        
                        // Add the current record
                        $balance += $record->debit_amount - $record->credit_amount;
                        
                        return $balance;
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('Ending Balance')
                            ->using(function ($query): string {
                                $year = (int) request()->query('year');
                                $month = (int) request()->query('month');
                                
                                // Get previous month balance
                                $prevBalance = $this->getPreviousMonthBalance();
                                
                                // Get all transactions for this month
                                $monthlyBalance = CashReport::where('cash_reference_id', $this->record->id)
                                    ->whereYear('transaction_date', $year)
                                    ->whereMonth('transaction_date', $month)
                                    ->sum(DB::raw('debit_amount - credit_amount'));
                                
                                // Calculate ending balance
                                $endingBalance = $prevBalance + $monthlyBalance;
                                
                                return number_format($endingBalance, 0, ',', '.');
                            })
                    )
                    ->alignEnd(),
            ])
            ->filters([
                // No additional filters needed here since we're already filtering by month/year
            ])
            ->actions([
                // No actions needed here
            ])
            ->striped()
            ->defaultSort('transaction_date', 'asc')
            ->paginated(false);
    }
    
    public function modifyTableQuery(Builder $query): Builder
    {
        // Just return the original query
        return $query;
    }
    
    // Generate a unique key for each table record
    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->getKey();
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('back')
                    ->label('Back to Monthly View')
                    ->url(CashReferenceResource::getUrl('viewMonthly', ['record' => $this->record]))
                    ->color('info')
                    ->icon('heroicon-o-arrow-left'),
                Actions\Action::make('viewAll')
                    ->label('View All Transactions')
                    ->url(CashReferenceResource::getUrl('view', ['record' => $this->record]))
                    ->color('success')
                    ->icon('heroicon-o-list-bullet'),
            ])->tooltip('Actions'),
        ];
    }
} 