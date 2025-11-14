<?php

namespace App\Filament\Resources\CashReferenceResource\Pages;

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
use App\Filament\Resources\CashReferenceResource;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Tables\Actions\EditAction;

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
                        // Get previous month balance
                        $prevBalance = $this->getPreviousMonthBalance();

                        // Get all cash reports for this month up to the current record
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

                                return number_format($endingBalance, 2, ',', '.');
                            })
                    )
                    ->alignEnd(),
            ])
            ->filters([
                // No additional filters needed here since we're already filtering by month/year
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        Forms\Components\TextInput::make('description')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('cash_reference_id')
                            ->required()
                            ->label('Cash Reference')
                            ->options(CashReference::all()->pluck('name', 'id'))
                            ->disabled(),
                        Forms\Components\Select::make('coa_id')
                            ->required()
                            ->label('Chart of Account')
                            ->searchable()
                            ->options(function () {
                                return Coa::all()->mapWithKeys(function ($coa) {
                                    return [$coa->id => $coa->code . ' - ' . $coa->name];
                                });
                            }),
                        Forms\Components\DatePicker::make('transaction_date')
                            ->required()
                            ->label('Transaction Date'),
                        Forms\Components\TextInput::make('debit_amount')
                            ->required()
                            ->label('Debit Amount')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('credit_amount')
                            ->required()
                            ->label('Credit Amount')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp'),
                    ])
                    ->modalWidth('lg'),
                // Use a custom per-row delete action implemented as a callback.
                // This avoids Filament's default DeleteAction which can trigger
                // redirects or route handling that in this page context led to
                // an invalid/missing `month` query parameter ("month must be
                // between 0 and 99, -1 given"). The callback deletes the
                // record directly and lets the table refresh normally.
                \Filament\Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->action(function (CashReport $record): void {
                        // Delete the model directly
                        $record->delete();
                    }),

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
            Actions\Action::make('create')
                ->label('Add Transaction')
                ->form([
                    Forms\Components\TextInput::make('description')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Hidden::make('cash_reference_id')
                        ->default($this->record->id),
                    Forms\Components\Select::make('coa_id')
                        ->required()
                        ->label('Chart of Account')
                        ->searchable()
                        ->options(function () {
                            return Coa::all()->mapWithKeys(function ($coa) {
                                return [$coa->id => $coa->code . ' - ' . $coa->name];
                            });
                        }),
                    Forms\Components\DatePicker::make('transaction_date')
                        ->required()
                        ->label('Transaction Date')
                        ->default(now()),
                    Forms\Components\Hidden::make('invoice_id')
                        ->default('0'),
                    Forms\Components\Hidden::make('mou_id')
                        ->default('0'),
                    Forms\Components\Hidden::make('cost_list_invoice_id')
                        ->default('0'),
                    Forms\Components\TextInput::make('debit_amount')
                        ->required()
                        ->label('Debit Amount')
                        ->numeric()
                        ->default(0)
                        ->prefix('Rp'),
                    Forms\Components\TextInput::make('credit_amount')
                        ->required()
                        ->label('Credit Amount')
                        ->numeric()
                        ->default(0)
                        ->prefix('Rp'),
                ])
                ->action(function (array $data): void {
                    CashReport::create($data);
                })
                ->modalWidth('lg')
                ->color('primary')
                ->icon('heroicon-o-plus'),
        ];
    }
}
