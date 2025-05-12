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
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;

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
        // Get year and month from URL parameters if available
        $year = request()->query('year');
        $month = request()->query('month');
        
        $query = CashReport::query()
            ->where('cash_reference_id', $this->record->id);
            
        // Apply year and month filters if provided in URL
        if ($year) {
            $query->whereYear('transaction_date', $year);
        }
        
        if ($month) {
            $query->whereMonth('transaction_date', $month);
        }
            
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
                // TextColumn::make('invoice.invoice_number')
                //     ->label('Invoice No')
                //     ->searchable()
                //     ->sortable(),
                // TextColumn::make('invoice.mou.mou_number')
                //     ->label('MoU No')
                //     ->searchable()
                //     ->sortable(),
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
                    )
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 0, ',', '.');
                    })
                    ->getStateUsing(function ($record, $column) {
                        // Get all cash reports for the same cash reference, ordered by date
                        $cashReports = CashReport::where('cash_reference_id', $record->cash_reference_id)
                            ->where(function($query) use ($record) {
                                $query->where('transaction_date', '<', $record->transaction_date)
                                    ->orWhere(function($q) use ($record) {
                                        $q->where('transaction_date', '=', $record->transaction_date)
                                            ->where('id', '<=', $record->id);
                                    });
                            })
                            ->orderBy('transaction_date')
                            ->orderBy('id')
                            ->get();
                        
                        // Calculate running balance
                        $balance = 0;
                        foreach ($cashReports as $report) {
                            $balance += $report->debit_amount - $report->credit_amount;
                        }
                        
                        return $balance;
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('Saldo Akhir')
                            ->using(function ($query): string {
                                // Get the last record ID to calculate final balance
                                $lastRecord = $query->latest('transaction_date')->latest('id')->first();
                                
                                if (!$lastRecord) {
                                    return number_format(0, 0, ',', '.');
                                }
                                
                                // Get all cash reports for this cash reference up to the last record
                                $cashReports = CashReport::where('cash_reference_id', $lastRecord->cash_reference_id)
                                    ->where(function($q) use ($lastRecord) {
                                        $q->where('transaction_date', '<', $lastRecord->transaction_date)
                                            ->orWhere(function($innerQ) use ($lastRecord) {
                                                $innerQ->where('transaction_date', '=', $lastRecord->transaction_date)
                                                    ->where('id', '<=', $lastRecord->id);
                                            });
                                    })
                                    ->orderBy('transaction_date')
                                    ->orderBy('id')
                                    ->get();
                                
                                // Calculate final balance
                                $finalBalance = 0;
                                foreach ($cashReports as $report) {
                                    $finalBalance += $report->debit_amount - $report->credit_amount;
                                }
                                
                                return number_format($finalBalance, 0, ',', '.');
                            })
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
                EditAction::make(),
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
            ])->striped()->defaultSort('transaction_date', 'asc');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to List')
                ->url(CashReferenceResource::getUrl('index'))
                ->color('info')
                ->icon('heroicon-o-arrow-left'),
            Actions\Action::make('viewMonthly')
                ->label('Monthly View')
                ->url(CashReferenceResource::getUrl('viewMonthly', ['record' => $this->record]))
                ->color('success')
                ->icon('heroicon-o-calendar'),
        ];
    }
} 