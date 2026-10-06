<?php

namespace App\Filament\App\Resources\JournalBookReferenceResource\Pages;

use App\Filament\App\Resources\JournalBookReferenceResource;
use App\Models\Coa;
use App\Models\JournalBookReference;
use App\Models\JournalBookReport;
use App\Services\JurnalPendapatanService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ViewJournalBookMonthly extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = JournalBookReferenceResource::class;

    protected static string $view = 'filament.resources.journal-book-reference-resource.pages.view-journal-book-monthly';

    public JournalBookReference $record;

    public function mount(): void
    {
        if ($this->isJurnalPendapatan()) {
            if (JournalBookReport::where('journal_book_id', $this->record->id)->count() === 0) {
                JurnalPendapatanService::syncAll();
            }
        }
    }

    public function isJurnalPendapatan(): bool
    {
        return JurnalPendapatanService::isJurnalPendapatan($this->record);
    }

    public function getTitle(): string
    {
        return 'Monthly Transaction - ' . $this->record->name;
    }

    public function getTableRecordKey($record): string
    {
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
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'asc')
            )
            ->columns([
                TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),
                TextColumn::make('month')
                    ->label('Month')
                    ->formatStateUsing(fn(int $state): string => Carbon::create()->month($state)->format('F'))
                    ->sortable(),
                TextColumn::make('transaction_count')
                    ->label('# of Transactions')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('total_debit')
                    ->label('Total Debit')
                    ->formatStateUsing(fn(float $state): string => number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->alignEnd()
                    ->summarize(
                        Sum::make()
                            ->label('Sum of Total Debit')
                            ->formatStateUsing(fn($state) => number_format((float)$state, 0, ',', '.'))
                    ),
                TextColumn::make('total_credit')
                    ->label('Total Credit')
                    ->formatStateUsing(fn(float $state): string => number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->alignEnd()
                    ->summarize(
                        Sum::make()
                            ->label('Sum of Total Credit')
                            ->formatStateUsing(fn($state) => number_format((float)$state, 0, ',', '.'))
                    ),
            ])
            ->filters([
                Filter::make('year')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                return JournalBookReport::where('journal_book_id', $this->record->id)
                                    ->selectRaw('DISTINCT YEAR(transaction_date) as year')
                                    ->orderBy('year', 'desc')
                                    ->pluck('year', 'year')
                                    ->toArray();
                            }),
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
                        $baseUrl = JournalBookReferenceResource::getUrl('monthDetail', ['record' => $this->record]);
                        return "{$baseUrl}?year={$year}&month={$month}";
                    })
                    ->icon('heroicon-o-eye')
                    ->color('primary')
            ])
            ->defaultSort('year', 'desc')
            ->striped();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make('create')
                ->label('Tambah Transaksi')
                ->model(JournalBookReport::class)
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->visible(fn() => !$this->isJurnalPendapatan() && static::getResource()::canCreate())
                ->form([
                    Forms\Components\DatePicker::make('transaction_date')
                        ->required()
                        ->label('Tanggal Transaksi')
                        ->default(now()),
                    Forms\Components\Textarea::make('description')
                        ->nullable()
                        ->maxLength(500)
                        ->label('Deskripsi'),
                    Forms\Components\Hidden::make('journal_book_id')
                        ->default(fn() => $this->record->id),
                    Forms\Components\Select::make('kepemilikan')
                        ->label('Kepemilikan')
                        ->options([
                            'PT' => 'PT',
                            'KKP' => 'KKP',
                        ])
                        ->default('KKP')
                        ->required(),
                    Forms\Components\Select::make('coa_id')
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
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['journal_book_id'] = $this->record->id;
                    return $data;
                })
                ->successNotificationTitle('Transaksi berhasil ditambahkan'),
            Actions\Action::make('syncAll')
                ->label('Sinkronkan Data Piutang')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi Semua Periode Jurnal Pendapatan')
                ->modalDescription('Perbarui dan sinkronkan seluruh data Jurnal Pendapatan dari kalkulasi transaksi Piutang (Invoice, Kas/Bank, MoU) ke seluruh bulan?')
                ->action(function (): void {
                    $count = JurnalPendapatanService::syncAll();
                    Notification::make()
                        ->title('Sinkronisasi Berhasil')
                        ->body("Seluruh data Jurnal Pendapatan berhasil disinkronkan ({$count} entri).")
                        ->success()
                        ->send();
                })
                ->visible(fn() => $this->isJurnalPendapatan()),
            Actions\Action::make('back')
                ->label('Back to List')
                ->url(JournalBookReferenceResource::getUrl('index'))
                ->color('info')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
