<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashReportResource\Pages;
use App\Filament\Resources\CashReportResource\RelationManagers;
use App\Models\CashReport;
use App\Models\CashReference;
use App\Models\Coa;
use App\Models\Invoice;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('cash_reference_id')
                    ->required()
                    ->options(CashReference::all()->pluck('name', 'id')),
                Forms\Components\Select::make('coa_id')   
                    ->required()
                    ->options(Coa::all()->pluck('name', 'id')),
                Forms\Components\Select::make('invoice_id')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state) {
                            $invoice = Invoice::find($state);
                            if ($invoice && $invoice->mou_id) {
                                $set('mou_id', $invoice->mou_id);
                            }
                        }
                    })
                    ->options(Invoice::all()->pluck('invoice_number', 'id')),
                Forms\Components\Hidden::make('mou_id')
                    ->required(),
                Forms\Components\Hidden::make('cost_list_invoice_id')
                    ->default(null),
                Forms\Components\TextInput::make('debit_amount')
                    ->required()
                    ->numeric(),    
                Forms\Components\TextInput::make('credit_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('transaction_date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                        return number_format((float)$state, 0, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Sum of Debit')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 0, ',', '.');
                            })
                    )
                    ->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('credit_amount')
                    ->numeric()
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 0, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Sum of Credit')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 0, ',', '.');
                            })
                    )
                    ->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 0, ',', '.');
                    })
                    ->getStateUsing(function ($record) {
                        return $record->debit_amount - $record->credit_amount;
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Balance')
                            ->using(function ($query): string {
                                $totalDebit = $query->sum('debit_amount');
                                $totalCredit = $query->sum('credit_amount');
                                return number_format($totalDebit - $totalCredit, 0, ',', '.');
                            })
                            
                    )
                    ->sortable()->alignEnd(),
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
                                fn (Builder $query, $month): Builder => $query->whereMonth('transaction_date', $month)
                            );
                    }),
                Tables\Filters\Filter::make('transaction_year')
                    ->label('Year')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(function() {
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
                                fn (Builder $query, $year): Builder => $query->whereYear('transaction_date', $year)
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
            ])->defaultSort('transaction_date', 'desc');
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
