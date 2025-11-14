<?php

namespace App\Filament\App\Resources\CashReferenceResource\Pages;

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
use App\Filament\App\Resources\CashReferenceResource;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Forms;

class ViewCashReferenceMonthly extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CashReferenceResource::class;

    protected static string $view = 'filament.resources.cash-reference-resource.pages.view-cash-reference-monthly';

    public CashReference $record;

    public function getTitle(): string
    {
        return 'Monthly Transaction - ' . $this->record->name;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CashReport::query()
                    ->where('cash_reference_id', $this->record->id)
                    ->select([
                        DB::raw('YEAR(transaction_date) as year'),
                        DB::raw('MONTH(transaction_date) as month'),
                        DB::raw('SUM(debit_amount) as total_debit'),
                        DB::raw('SUM(credit_amount) as total_credit'),
                        DB::raw('SUM(debit_amount - credit_amount) as monthly_balance'),
                        DB::raw('COUNT(*) as transaction_count')
                    ])
                    ->groupBy('year', 'month')
            )
            ->columns([
                TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),
                TextColumn::make('month')
                    ->label('Month')
                    ->formatStateUsing(function ($state) {
                        return Carbon::create()->month($state)->format('F');
                    })
                    ->sortable(),
                TextColumn::make('transaction_count')
                    ->label('# of Transactions')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('total_debit')
                    ->label('Total Debit')
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 2, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 2, ',', '.');
                            })
                    )
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('total_credit')
                    ->label('Total Credit')
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 2, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 2, ',', '.');
                            })
                    )
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('monthly_balance')
                    ->label('Monthly Balance')
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 2, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 2, ',', '.');
                            })
                    )
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([
                Filter::make('year')
                    ->form([
                        Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = CashReport::where('cash_reference_id', $this->record->id)
                                    ->selectRaw('DISTINCT YEAR(transaction_date) as year')
                                    ->orderBy('year', 'desc')
                                    ->pluck('year', 'year')
                                    ->toArray();
                                return $years;
                            })
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['year']) && $data['year']) {
                            return $query->having('year', '=', $data['year']);
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Action::make('viewDetails')
                    ->label('View Transactions')
                    ->url(function ($record) {
                        $year = $record->year;
                        $month = $record->month;
                        $baseUrl = CashReferenceResource::getUrl('monthDetail', ['record' => $this->record]);
                        return "{$baseUrl}?year={$year}&month={$month}";
                    })
                    ->icon('heroicon-o-eye')
                    ->color('primary')
            ])
            ->defaultSort('year', 'desc')
            ->defaultSort('month', 'desc')
            ->striped();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to List')
                ->url(CashReferenceResource::getUrl('index'))
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

    public function getTableRecordKey($record): string
    {
        return (string) ($record->year . '-' . $record->month);
    }
}
