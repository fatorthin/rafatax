<?php

namespace App\Filament\App\Resources\JournalBookReferenceResource\Pages;

use App\Filament\App\Resources\JournalBookReferenceResource;
use App\Models\Coa;
use App\Models\JournalBookReference;
use App\Models\JournalBookReport;
use App\Services\JurnalPendapatanService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ViewJournalBookMonthDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = JournalBookReferenceResource::class;

    protected static string $view = 'filament.resources.journal-book-reference-resource.pages.view-journal-book-month-detail';

    public JournalBookReference $record;
    public ?int $year = null;
    public ?int $month = null;

    public function mount(): void
    {
        $this->year = (int) request('year');
        $this->month = (int) request('month');

        if ($this->isJurnalPendapatan() && $this->year && $this->month) {
            JurnalPendapatanService::syncMonth($this->year, $this->month);
        }
    }

    public function isJurnalPendapatan(): bool
    {
        return JurnalPendapatanService::isJurnalPendapatan($this->record);
    }

    public function getTitle(): string
    {
        $monthName = Carbon::create()->month($this->month)->format('F');

        return "Transactions - {$this->record->name} - {$monthName} {$this->year}";
    }

    public function table(Table $table): Table
    {
        $year = $this->year;
        $month = $this->month;

        if (!$year || !$month) {
            return $table->query(JournalBookReport::where('id', 0));
        }

        $isJurnalPendapatan = $this->isJurnalPendapatan();

        return $table
            ->query(
                JournalBookReport::query()
                    ->where('journal_book_id', $this->record->id)
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month)
            )
            ->defaultSort('transaction_date', 'desc')
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal Transaksi')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('journal_book.name')
                    ->label('Buku Jurnal')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('coa.name')
                    ->label('CoA')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('debit_amount')
                    ->label('Debit')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 0, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->label('Sum of Debit')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 0, ',', '.');
                            })
                    ),
                TextColumn::make('credit_amount')
                    ->label('Kredit')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 0, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->label('Sum of Credit')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 0, ',', '.');
                            })
                    ),
            ])
            ->filters([
                SelectFilter::make('coa_id')
                    ->label('COA')
                    ->options(function () {
                        return Coa::all()->mapWithKeys(function ($coa) {
                            return [$coa->id => $coa->code . ' - ' . $coa->name];
                        });
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => !$isJurnalPendapatan && static::getResource()::canEdit($this->record))
                    ->form([
                        Forms\Components\Textarea::make('description')
                            ->nullable()
                            ->maxLength(500)
                            ->label('Deskripsi'),
                        Forms\Components\Hidden::make('journal_book_id')
                            ->default($this->record->id),
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
                        Forms\Components\DatePicker::make('transaction_date')
                            ->required()
                            ->label('Tanggal Transaksi'),
                    ]),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => !$isJurnalPendapatan && static::getResource()::canDelete($this->record))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => !$isJurnalPendapatan && static::getResource()::canDelete($this->record)),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncMonth')
                ->label('Sinkronkan Bulan Ini')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading("Sinkronisasi Jurnal Pendapatan - Periode {$this->month}/{$this->year}")
                ->modalDescription("Perbarui data Jurnal Pendapatan bulan ini berdasarkan kalkulasi Piutang (Invoice, Kas/Bank, MoU)? Data sebelumnya untuk bulan ini akan diperbarui.")
                ->action(function (): void {
                    $count = JurnalPendapatanService::syncMonth($this->year, $this->month);
                    \Filament\Notifications\Notification::make()
                        ->title('Sinkronisasi Berhasil')
                        ->body("Data Jurnal Pendapatan periode {$this->month}/{$this->year} berhasil diperbarui ({$count} entri).")
                        ->success()
                        ->send();
                })
                ->visible(fn() => $this->isJurnalPendapatan() && $this->year && $this->month),
            Actions\Action::make('back')
                ->label('Back to Monthly View')
                ->url(JournalBookReferenceResource::getUrl('viewMonthly', ['record' => $this->record]))
                ->color('info')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
