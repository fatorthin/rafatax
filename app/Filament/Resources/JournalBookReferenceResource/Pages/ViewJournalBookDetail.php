<?php

namespace App\Filament\Resources\JournalBookReferenceResource\Pages;

use App\Models\Coa;
use Filament\Forms;
use Filament\Actions;
use Filament\Tables\Table;
use App\Models\JournalBookReport;
use Filament\Resources\Pages\Page;
use Filament\Tables\Filters\Filter;
use App\Models\JournalBookReference;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use App\Filament\Resources\JournalBookReferenceResource;

class ViewJournalBookDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = JournalBookReferenceResource::class;

    protected static string $view = 'filament.resources.journal-book-reference-resource.pages.view-journal-book-detail';

    public JournalBookReference $record;

    public function getTitle(): string
    {
        return 'Detail ' . $this->record->name;
    }

    public function table(Table $table): Table
    {
        // Get year and month from URL parameters if available
        $year = request()->query('year');
        $month = request()->query('month');

        $query = JournalBookReport::query()
            ->where('journal_book_id', $this->record->id);

        // Apply year and month filters if provided in URL
        if ($year) {
            $query->whereYear('transaction_date', $year);
        }

        if ($month) {
            $query->whereMonth('transaction_date', $month);
        }

        // dd($query);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('transaction_date')
                    ->dateTime('d-M-Y')
                    ->sortable(),
                TextColumn::make('coa.code')
                    ->label('CoA')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('coa.name')
                    ->label('Keterangan CoA')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable(),
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
                            ->label('Sum of Debit')
                    )
                    ->sortable()
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
                            ->label('Sum of Credit')
                    )
                    ->sortable()
                    ->alignEnd(),

            ])
            ->filters([
                SelectFilter::make('coa_id')
                    ->label('CoA')
                    ->options(Coa::all()->pluck('name', 'id')),
                Filter::make('transaction_month')
                    ->form([
                        Select::make('month')
                            ->label('Transaction Month')
                            ->options([
                                '1' => 'January',
                                '2' => 'February',
                                '3' => 'March',
                                '4' => 'April',
                                '5' => 'May',
                                '6' => 'June',
                                '7' => 'July',
                                '8' => 'August',
                                '9' => 'September',
                                '10' => 'October',
                                '11' => 'November',
                                '12' => 'December'
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['month'],
                                fn(Builder $query, $month): Builder => $query->whereMonth('transaction_date', $month)
                            );
                    }),

                Filter::make('transaction_year')
                    ->form([
                        Select::make('year')
                            ->label('Transaction Year')
                            ->options(array_combine(
                                range(date('Y'), date('Y') - 5),
                                range(date('Y'), date('Y') - 5)
                            ))
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year'],
                                fn(Builder $query, $year): Builder => $query->whereYear('transaction_date', $year)
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->url(fn(JournalBookReport $record) => route('filament.admin.resources.journal-book-reports.edit', ['record' => $record])),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->striped()
            ->defaultSort('transaction_date', 'asc');
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
