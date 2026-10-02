<?php

namespace App\Filament\App\Resources\JournalBookReferenceResource\Pages;

use App\Filament\App\Resources\JournalBookReferenceResource;
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
        $subquery = JournalBookReport::query()
            ->selectRaw('
                YEAR(transaction_date) as year,
                MONTH(transaction_date) as month,
                SUM(debit_amount) as total_debit,
                SUM(credit_amount) as total_credit,
                COUNT(*) as transaction_count
            ')
            ->where('journal_book_id', $this->record->id)
            ->groupBy(DB::raw('YEAR(transaction_date)'), DB::raw('MONTH(transaction_date)'));

        return $table
            ->query(
                JournalBookReport::query()
                    ->fromSub($subquery, 'monthly_transactions')
            )
            ->columns([
                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('month')
                    ->label('Month')
                    ->formatStateUsing(fn(int $state): string => Carbon::create()->month($state)->format('F'))
                    ->sortable()
                    ->searchable(),
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
                TextColumn::make('transaction_count')
                    ->label('Total Transactions')
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([
                Filter::make('year')
                    ->form([
                        Forms\Components\TextInput::make('year')
                            ->label('Year')
                            ->numeric()
                            ->maxLength(4),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['year'],
                            fn(Builder $query, $year): Builder => $query->where('year', $year)
                        );
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
