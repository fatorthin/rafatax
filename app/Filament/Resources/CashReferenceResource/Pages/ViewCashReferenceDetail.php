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
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\CashReferenceResource;

class ViewCashReferenceDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CashReferenceResource::class;
    
    protected static string $view = 'filament.resources.cash-reference-resource.pages.view-cash-reference-detail';
    
    public CashReference $record;
    
    public function getTitle(): string
    {
        return 'Detail ' . $this->record->name;
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                CashReport::query()
                    ->where('cash_reference_id', $this->record->id)
            )
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
                TextColumn::make('invoice.invoice_number')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.mou.mou_number')
                    ->label('MoU No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('debit_amount')
                    ->numeric()
                    ->money('IDR')
                    ->summarize(
                        Sum::make()
                            ->money('IDR')
                    )
                    ->sortable(),
                TextColumn::make('credit_amount')
                    ->numeric()
                    ->money('IDR')
                        ->summarize(
                            Sum::make()
                            ->money('IDR')
                    )
                    ->sortable(),
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
                                fn (Builder $query, $month): Builder => $query->whereMonth('transaction_date', $month)
                            );
                    }),

                Filter::make('transaction_year')
                    ->form([
                        Select::make('year')
                            ->label('Transaction Year')
                            ->options(array_combine(
                                range(date('Y'), date('Y')-5),
                                range(date('Y'), date('Y')-5)
                            ))
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year'],
                                fn (Builder $query, $year): Builder => $query->whereYear('transaction_date', $year)
                            );
                    })
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to List')
                ->url(CashReferenceResource::getUrl('index'))
                ->color('info')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
} 