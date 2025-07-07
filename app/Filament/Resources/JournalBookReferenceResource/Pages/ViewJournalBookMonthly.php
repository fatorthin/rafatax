<?php

namespace App\Filament\Resources\JournalBookReferenceResource\Pages;

use Filament\Tables\Table;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Support\Carbon;
use App\Models\JournalBookReport;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use App\Models\JournalBookReference;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\JournalBookReferenceResource;
use App\Models\Coa;

class ViewJournalBookMonthly extends Page implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = JournalBookReferenceResource::class;

    protected static string $view = 'filament.resources.journal-book-reference-resource.pages.view-journal-book-monthly';

    public JournalBookReference $record;

    public function getTitle(): string
    {
        return 'Monthly Transaction - ' . $this->record->name;
    }

    public function getTableRecordKey($record): string
    {
        // Combine year and month as a unique key
        return "{$record->year}-{$record->month}";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JournalBookReport::query()
                    ->where('journal_book_id', $this->record->id)
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
                        return number_format((float) $state, 0, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 0, ',', '.');
                            })
                    )
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('total_credit')
                    ->label('Total Credit')
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 0, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 0, ',', '.');
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
                                $years = JournalBookReport::where('journal_book_id', $this->record->id)
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
                        // Use query parameters to filter transactions by year and month
                        $year = $record->year;
                        $month = $record->month;
                        $baseUrl = JournalBookReferenceResource::getUrl('monthDetail', ['record' => $this->record]);
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
                ->url(JournalBookReferenceResource::getUrl('index'))
                ->color('info')
                ->icon('heroicon-o-arrow-left'),
            Actions\Action::make('viewAll')
                ->label('View All Transactions')
                ->url(JournalBookReferenceResource::getUrl('view', ['record' => $this->record]))
                ->color('success')
                ->icon('heroicon-o-list-bullet'),
            Actions\Action::make('create')
                ->form([
                    Forms\Components\Textarea::make('description')
                        ->nullable()
                        ->maxLength(500)
                        ->label('Deskripsi'),
                    Forms\Components\Hidden::make('journal_book_id')
                        ->default($this->record->id),
                    Forms\Components\Select::make(name: 'coa_id')
                        ->label('CoA')
                        ->options(fn() => Coa::all()->mapWithKeys(fn($coa) => [
                            $coa->id => "{$coa->code} - {$coa->name}"
                        ]))
                        ->required()
                        ->searchable(),
                    Forms\Components\TextInput::make('debit_amount')
                        ->numeric()
                        ->required()
                        ->label('Debit')
                        ->default(0),
                    Forms\Components\TextInput::make('credit_amount')
                        ->numeric()
                        ->required()
                        ->label('Kredit')
                        ->default(0),
                    Forms\Components\DatePicker::make('transaction_date')
                        ->required()
                        ->label('Tanggal Transaksi'),
                ])
                ->action(function (array $data): void {
                    JournalBookReport::create($data);
                })
                ->label('Add New Data')
                ->icon('heroicon-o-plus')
        ];
    }
}
