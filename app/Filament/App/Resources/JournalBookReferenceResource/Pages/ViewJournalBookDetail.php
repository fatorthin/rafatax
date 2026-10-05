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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewJournalBookDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = JournalBookReferenceResource::class;

    protected static string $view = 'filament.resources.journal-book-reference-resource.pages.view-journal-book-detail';

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
        return 'Detail ' . $this->record->name;
    }

    public function table(Table $table): Table
    {
        $isJurnalPendapatan = $this->isJurnalPendapatan();

        return $table
            ->query(
                JournalBookReport::query()
                    ->where('journal_book_id', $this->record->id)
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
                TextColumn::make('kepemilikan')
                    ->label('Kepemilikan')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'PT' => 'primary',
                        'KKP' => 'success',
                        default => 'gray',
                    }),
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
                SelectFilter::make('kepemilikan')
                    ->label('Kepemilikan')
                    ->options([
                        'PT' => 'PT',
                        'KKP' => 'KKP',
                    ]),
                SelectFilter::make('coa_id')
                    ->label('COA')
                    ->options(function () {
                        return Coa::all()->mapWithKeys(function ($coa) {
                            return [$coa->id => $coa->code . ' - ' . $coa->name];
                        });
                    })
                    ->searchable()
                    ->preload(),
                Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
                TrashedFilter::make(),
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
                        Forms\Components\DatePicker::make('transaction_date')
                            ->required()
                            ->label('Tanggal Transaksi'),
                    ]),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => !$isJurnalPendapatan && static::getResource()::canDelete($this->record))
                    ->requiresConfirmation(),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn() => !$isJurnalPendapatan && static::getResource()::canForceDelete($this->record))
                    ->requiresConfirmation(),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn() => !$isJurnalPendapatan),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => !$isJurnalPendapatan && static::getResource()::canDelete($this->record)),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => !$isJurnalPendapatan && static::getResource()::canForceDelete($this->record)),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn() => !$isJurnalPendapatan),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to List')
                ->url(JournalBookReferenceResource::getUrl('index'))
                ->color('info')
                ->icon('heroicon-o-arrow-left'),
            Actions\Action::make('create')
                ->visible(fn() => !$this->isJurnalPendapatan() && static::getResource()::canCreate())
                ->form([
                    Forms\Components\Textarea::make('description')
                        ->nullable()
                        ->maxLength(500)
                        ->label('Deskripsi'),
                    Forms\Components\Hidden::make('journal_book_id')
                        ->default($this->record->id),
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
