<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashReportResource\Pages;
use App\Models\CashReport;
use App\Models\CashReference;
use App\Models\Coa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CashReportResource extends Resource
{
    protected static ?string $model = CashReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Histori Kas';
    protected static ?string $navigationGroup = 'Bagian Keuangan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('cash_reference_id')
                    ->required()
                    ->default(function () {
                        return request()->query('cash_reference_id');
                    })
                    ->options(CashReference::all()->pluck('name', 'id')),
                Forms\Components\Select::make('coa_id')
                    ->required()
                    ->searchable()
                    ->options(function () {
                        return Coa::all()->mapWithKeys(function ($coa) {
                            return [$coa->id => $coa->code . ' - ' . $coa->name];
                        });
                    }),
                Forms\Components\DatePicker::make('transaction_date')
                    ->required(),
                Forms\Components\Hidden::make('invoice_id')
                    ->default('0'),
                Forms\Components\Hidden::make('mou_id')
                    ->default('0'),
                Forms\Components\Hidden::make('cost_list_invoice_id')
                    ->default('0'),
                Forms\Components\TextInput::make('debit_amount')
                    ->required()
                    ->label('Debit')
                    ->default(0)
                    ->numeric(),
                Forms\Components\TextInput::make('credit_amount')
                    ->required()
                    ->label('Credit')
                    ->default(0)
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('cashReference.name')
                    ->label('Cash Reference')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->dateTime('d-M-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('coa.code')
                    ->label('CoA')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice.mou.id')
                    ->label('MoU ID')
                    ->numeric()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invoice_id')
                    ->numeric()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cost_list_invoice_id')
                    ->numeric()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('debit_amount')
                    ->numeric()
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 2, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Sum of Debit')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 2, ',', '.');
                            })
                    )
                    ->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('credit_amount')
                    ->numeric()
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 2, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Sum of Credit')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 2, ',', '.');
                            })
                    )
                    ->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 2, ',', '.');
                    })
                    ->getStateUsing(function ($record, $column) {
                        // Get all cash reports for the same cash reference, ordered by date
                        $cashReports = CashReport::where('cash_reference_id', $record->cash_reference_id)
                            ->where(function ($query) use ($record) {
                                $query->where('transaction_date', '<', $record->transaction_date)
                                    ->orWhere(function ($q) use ($record) {
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
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Saldo Akhir')
                            ->using(function ($query): string {
                                // Get the last record ID to calculate final balance
                                $lastRecord = $query->latest('transaction_date')->latest('id')->first();

                                if (!$lastRecord) {
                                    return number_format(2, 0, ',', '.');
                                }

                                // Get all cash reports for this cash reference up to the last record
                                $cashReports = CashReport::where('cash_reference_id', $lastRecord->cash_reference_id)
                                    ->where(function ($q) use ($lastRecord) {
                                        $q->where('transaction_date', '<', $lastRecord->transaction_date)
                                            ->orWhere(function ($innerQ) use ($lastRecord) {
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

                                return number_format($finalBalance, 2, ',', '.');
                            })
                    )
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('cash_reference_id')
                    ->label('Cash Reference')
                    ->relationship('cashReference', 'name'),
                Tables\Filters\SelectFilter::make('coa_id')
                    ->label('Chart of Account')
                    ->relationship('coa', 'name'),
                Tables\Filters\Filter::make('transaction_month')
                    ->label('Month')
                    ->form([
                        Forms\Components\Select::make('month')
                            ->label('Month')
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
                                '12' => 'December',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['month'],
                                fn(Builder $query, $month): Builder => $query->whereMonth('transaction_date', $month)
                            );
                    }),
                Tables\Filters\Filter::make('transaction_year')
                    ->label('Year')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = [];
                                $currentYear = now()->year;
                                for ($i = $currentYear - 5; $i <= $currentYear; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year'],
                                fn(Builder $query, $year): Builder => $query->whereYear('transaction_date', $year)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'asc')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashReports::route('/'),
            'create' => Pages\CreateCashReport::route('/create'),
            'edit' => Pages\EditCashReport::route('/{record}/edit'),
            'neraca-lajur' => Pages\NeracaLajurBulanan::route('/neraca-lajur'),
            'neraca' => Pages\Neraca::route('/neraca'),
            'laba-rugi-bulanan' => Pages\LabaRugiBulanan::route('/laba-rugi-bulanan'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
