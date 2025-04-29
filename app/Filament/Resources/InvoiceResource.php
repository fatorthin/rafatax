<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\MoU;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('mou_id')
                    ->label('MoU')
                    ->options(function () {
                        return MoU::query()
                            ->select('id', 'mou_number')
                            ->get()
                            ->pluck('mou_number', 'id');
                    })
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('invoice_number')
                    ->required()
                    ->maxLength(255)
                    ->unique(Invoice::class, 'invoice_number', fn ($record) => $record),
                Forms\Components\DatePicker::make('invoice_date')
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                Forms\Components\Select::make('invoice_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null)
            ->paginated([10, 25, 50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mou.mou_number')
                    ->label('MoU Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mou.client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mou.type')
                    ->label('Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_status')
                    ->label('Status'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->alignEnd()
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 0, ',', '.');
                    })
                    ->getStateUsing(function ($record) {
                        return $record->costListInvoices()->sum('amount');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total')
                            ->using(function ($query) {
                                // Get all invoice IDs from the current query
                                $invoiceIds = $query->pluck('id')->toArray();
                                
                                // Calculate total from the cost_list_invoices table
                                $total = \App\Models\CostListInvoice::whereIn('invoice_id', $invoiceIds)
                                    ->sum('amount');
                                
                                return $total;
                            })
                            ->formatStateUsing(function ($state) {
                                return 'IDR ' . number_format((float) $state, 0, ',', '.');
                            })
                    ),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
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
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('year')
                                    ->label('Year')
                                    ->options(
                                        Invoice::query()
                                            ->selectRaw('YEAR(invoice_date) as year')
                                            ->distinct()
                                            ->orderBy('year', 'desc')
                                            ->pluck('year', 'year')
                                            ->toArray()
                                    ),
                                Forms\Components\Select::make('month')
                                    ->label('Month')
                                    ->options(
                                        collect(range(1, 12))->mapWithKeys(function ($month) {
                                            return [$month => \Carbon\Carbon::create()->month($month)->format('F')];
                                        })->toArray()
                                    ),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year'],
                                fn (Builder $query, $year): Builder => $query->whereYear('invoice_date', $year),
                            )
                            ->when(
                                $data['month'],
                                fn (Builder $query, $month): Builder => $query->whereMonth('invoice_date', $month),
                            );
                    })
                    ->indicator(function (array $data): ?string {
                        $indicators = [];
                        
                        if ($data['month'] ?? null) {
                            $monthName = \Carbon\Carbon::create()->month($data['month'])->format('F');
                            $indicators[] = "Month: {$monthName}";
                        }
                        
                        if ($data['year'] ?? null) {
                            $indicators[] = "Year: {$data['year']}";
                        }
                        
                        return count($indicators) ? implode(' + ', $indicators) : null;
                    }),
                Tables\Filters\SelectFilter::make('client')
                    ->label('Client')
                    ->relationship('mou.client', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'pt' => 'PT',
                        'consultant' => 'Consultant',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query, $type): Builder => $query->whereHas('mou', fn ($q) => $q->where('type', $type)),
                            );
                    }),
                Tables\Filters\SelectFilter::make('invoice_status')
                    ->label('Status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->color('warning'),
                Tables\Actions\Action::make('viewCostList')
                    ->label('Detail')
                    ->url(fn($record) => "/admin/invoices/{$record->id}/cost-list")
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                Tables\Actions\DeleteAction::make()->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])->defaultSort('invoice_date', 'desc');
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'viewCostList' => Pages\ListCostInvoice::route('/{record}/cost-list'),
            'cost-create' => Pages\CreateCostInvoice::route('/{record}/cost-create'),
            'cost-edit' => Pages\EditCostInvoice::route('/{record}/cost-edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->latest('created_at');
    }
}
