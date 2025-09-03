<?php

namespace App\Filament\App\Resources\CashReferenceResource\Pages;

use Carbon\Carbon;
use App\Models\Coa;
use Filament\Actions;
use App\Models\CashReport;
use Filament\Tables\Table;
use App\Models\CashReference;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\App\Resources\CashReferenceResource;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ViewCashReferenceMonthDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CashReferenceResource::class;

    protected static string $view = 'filament.resources.cash-reference-resource.pages.view-cash-reference-month-detail';

    public CashReference $record;

    public function getTitle(): string
    {
        $year = request()->query('year');
        $month = (int) request()->query('month');

        $monthName = Carbon::create()->month($month)->format('F');

        return "Transactions - {$this->record->name} - {$monthName} {$year}";
    }

    protected function getPreviousMonthBalance(): float
    {
        $year = (int) request()->query('year');
        $month = (int) request()->query('month');

        $prevMonth = $month - 1;
        $prevYear = $year;

        if ($prevMonth === 0) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        }

        $lastDayPrevMonth = Carbon::create($prevYear, $prevMonth)->endOfMonth()->format('Y-m-d');

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
            return $table->query(CashReport::where('id', 0));
        }

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
                        return number_format((float) $state, 2, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 2, ',', '.');
                            })
                            ->label('Sum of Dedit')
                    )
                    ->alignEnd(),
                TextColumn::make('credit_amount')
                    ->numeric()
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 2, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 2, ',', '.');
                            })
                            ->label('Sum of Credit')
                    )
                    ->alignEnd(),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 2, ',', '.');
                    })
                    ->getStateUsing(function ($record, $column) {
                        $prevBalance = $this->getPreviousMonthBalance();

                        $year = (int) request()->query('year');
                        $month = (int) request()->query('month');

                        $cashReports = CashReport::where('cash_reference_id', $record->cash_reference_id)
                            ->whereYear('transaction_date', $year)
                            ->whereMonth('transaction_date', $month)
                            ->where(function ($q) use ($record) {
                                $q->where('transaction_date', '<', $record->transaction_date)
                                    ->orWhere(function ($innerQ) use ($record) {
                                        $innerQ->where('transaction_date', '=', $record->transaction_date)
                                            ->where('id', '<', $record->id);
                                    });
                            })
                            ->orderBy('transaction_date')
                            ->orderBy('id')
                            ->get();

                        $balance = $prevBalance;
                        foreach ($cashReports as $report) {
                            $balance += $report->debit_amount - $report->credit_amount;
                        }

                        $balance += $record->debit_amount - $record->credit_amount;

                        return $balance;
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('Ending Balance')
                            ->using(function ($query): string {
                                $year = (int) request()->query('year');
                                $month = (int) request()->query('month');

                                $prevBalance = $this->getPreviousMonthBalance();

                                $monthlyBalance = CashReport::where('cash_reference_id', $this->record->id)
                                    ->whereYear('transaction_date', $year)
                                    ->whereMonth('transaction_date', $month)
                                    ->sum(DB::raw('debit_amount - credit_amount'));

                                $endingBalance = $prevBalance + $monthlyBalance;

                                return number_format($endingBalance, 2, ',', '.');
                            })
                    )
                    ->alignEnd(),
            ])
            ->filters([
                // Already filtered by year and month via query params
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->url(fn(CashReport $record) => route('filament.admin.resources.cash-reports.edit', ['record' => $record])),
            ])
            ->striped()
            ->defaultSort('transaction_date', 'asc')
            ->paginated(false);
    }

    public function modifyTableQuery(Builder $query): Builder
    {
        return $query;
    }

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->getKey();
    }

    protected function getHeaderActions(): array
    {
        return [
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
            Actions\Action::make('addTransaction')
                ->label('Add Transaction')
                ->url(fn() => route('filament.admin.resources.cash-reports.create', ['cash_reference_id' => $this->record->id]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
        ];
    }
}
