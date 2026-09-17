<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceMemoResource\Pages;
use App\Models\CashReport;
use App\Models\Coa;
use App\Models\CostListInvoice;
use App\Models\Invoice;
use App\Models\Memo;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class InvoiceMemoResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $slug = 'invoices-memo';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Daftar Invoice Memo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('memo_id')
                    ->label('Memo')
                    ->options(function () {
                        return Memo::query()
                            ->select(['id', 'no_memo', 'description', 'nama_klien', 'instansi_klien'])
                            ->get()
                            ->mapWithKeys(function ($memo) {
                                $client = $memo->nama_klien ?? $memo->instansi_klien ?? '-';
                                return [$memo->id => $memo->no_memo.' - '.$client.' - '.$memo->description];
                            });
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $memo = Memo::find($state);
                            if ($memo && $memo->tipe_klien) {
                                $set('invoice_type', strtolower($memo->tipe_klien));
                            }
                        }
                        self::generateInvoiceNumber($set, $get);
                    }),
                Forms\Components\TextInput::make('invoice_number')
                    ->required()
                    ->maxLength(255)
                    ->readOnly()
                    ->unique(
                        Invoice::class,
                        'invoice_number',
                        fn ($record) => $record,
                        modifyRuleUsing: function ($rule) {
                            return $rule->whereNull('deleted_at');
                        }
                    )
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('regenerate_number')
                            ->icon('heroicon-m-arrow-path')
                            ->tooltip('Regenerate Invoice Number')
                            ->action(function (Forms\Set $set, Forms\Get $get) {
                                self::generateInvoiceNumber($set, $get);
                            })
                    ),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('invoice_date')
                    ->required()
                    ->live()
                    ->default(now())
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $dueDate = date('Y-m-d', strtotime($state.' + 2 weeks'));
                            $set('due_date', $dueDate);
                        }
                        self::generateInvoiceNumber($set, $get);
                    }),
                Forms\Components\DatePicker::make('due_date')
                    ->required()
                    ->default(now()->addWeeks(2)),
                Forms\Components\Select::make('invoice_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                    ])
                    ->required()
                    ->default('unpaid'),
                Forms\Components\Select::make('invoice_type')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                        self::generateInvoiceNumber($set, $get);
                        if ($state === 'pt') {
                            $set('is_include_pph23', true);
                        } else {
                            $set('is_include_pph23', false);
                        }
                    }),
                Forms\Components\Checkbox::make('is_saldo_awal')
                    ->label('Checklist Invoice Saldo Awal')
                    ->default(false)
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        self::generateInvoiceNumber($set, $get);
                    }),
                Forms\Components\Checkbox::make('is_include_pph23')
                    ->label('Checklist Invoice PPH23')
                    ->default(false),
                Forms\Components\Select::make('rek_transfer')
                    ->label('Rekening Transfer')
                    ->options([
                        'BCA PT' => 'BCA PT',
                        'BCA 425' => 'BCA 425',
                        'BCA 140' => 'BCA 140',
                        'BCA 436' => 'BCA 436',
                        'MANDIRI' => 'MANDIRI',
                    ]),
                Forms\Components\DatePicker::make('tgl_transfer')
                    ->label('Tanggal Transfer'),
                Forms\Components\Select::make('is_send_invoice')
                    ->label('Status Kirim Invoice')
                    ->options([
                        1 => 'Sudah',
                        0 => 'Belum',
                    ])
                    ->default(0),
                Forms\Components\DatePicker::make('send_invoice_date')
                    ->label('Tanggal Kirim Invoice')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\Section::make('Rincian Biaya')
                    ->schema([
                        Forms\Components\Repeater::make('costListInvoices')
                            ->relationship()
                            ->schema([
                                Forms\Components\Hidden::make('mou_id')
                                    ->default(null),
                                Forms\Components\Select::make('coa_id')
                                    ->label('CoA')
                                    ->options(Coa::query()->where('group_coa_id', '40')->orWhereIn('id', [162, 181])->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->columnSpan([
                                        'md' => 3,
                                    ]),
                                Forms\Components\TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->columnSpan([
                                        'md' => 4,
                                    ]),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Harga')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan([
                                        'md' => 5,
                                    ]),
                            ])
                            ->columns([
                                'md' => 12,
                            ])
                            ->defaultItems(0)
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null)
            ->paginated([10, 25, 50, 100])
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->filtersLayout(Tables\Enums\FiltersLayout::Modal)
            ->filtersFormWidth(\Filament\Support\Enums\MaxWidth::FourExtraLarge)
            ->actions(static::getTableActions(), position: ActionsPosition::BeforeCells)
            ->bulkActions(static::getTableBulkActions())
            ->defaultSort('invoice_date', 'desc')
            ->deferLoading()
            ->description(new HtmlString('<style>.fi-ta-content { max-height: 75vh; overflow: auto !important; } .fi-ta-table thead { position: sticky; top: 0; z-index: 10; } .fi-ta-table thead th { background-color: #f9fafb; } .dark .fi-ta-table thead th { background-color: #18181b; } .invoice-sticky-column { background-color: #ffffff !important; color: #111827 !important; } .invoice-sticky-column * { color: inherit !important; } .dark .invoice-sticky-column { background-color: #18181b !important; color: #f4f4f5 !important; } .invoice-sticky-shadow { box-shadow: inset -2px 0 4px -2px rgba(0,0,0,0.1); }</style>'));
    }

    private static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('invoice_number')
                ->searchable()
                ->extraHeaderAttributes([
                    'style' => 'position: sticky; left: 0; z-index: 15; background-color: inherit;',
                    'class' => 'invoice-sticky-column w-[200px] min-w-[200px]',
                ])
                ->extraAttributes([
                    'style' => 'position: sticky; left: 0; z-index: 10;',
                    'class' => 'invoice-sticky-column invoice-sticky-shadow w-[200px] min-w-[200px]',
                ]),
            Tables\Columns\TextColumn::make('reference_number')
                ->label('Memo Number')
                ->getStateUsing(fn ($record) => $record->memo?->no_memo ?? '-')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->whereHas('memo', fn ($q) => $q->where('no_memo', 'like', "%{$search}%"));
                })
                ->extraHeaderAttributes([
                    'style' => 'position: sticky; left: 200px; z-index: 30; background-color: inherit; box-shadow: inset -2px 0 4px -2px rgba(0,0,0,0.1);',
                    'class' => 'invoice-sticky-column invoice-sticky-shadow w-[200px] min-w-[200px]',
                ])
                ->extraAttributes([
                    'style' => 'position: sticky; left: 200px; z-index: 10; box-shadow: inset -2px 0 4px -2px rgba(0,0,0,0.1);',
                    'class' => 'invoice-sticky-column invoice-sticky-shadow w-[200px] min-w-[200px]',
                ]),
            Tables\Columns\TextColumn::make('client_name')
                ->label('Client')
                ->getStateUsing(fn ($record) => $record->client_name)
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->whereHas('memo', fn ($subQ) => $subQ->where('nama_klien', 'like', "%{$search}%")->orWhere('instansi_klien', 'like', "%{$search}%"))
                          ->orWhereHas('client', fn ($subQ) => $subQ->where('company_name', 'like', "%{$search}%"));
                    });
                }),
            Tables\Columns\TextColumn::make('type')
                ->label('Type')
                ->getStateUsing(fn ($record) => $record->memo?->tipe_klien ?? $record->invoice_type)
                ->formatStateUsing(fn ($state) => match (strtolower($state ?? '')) {
                    'pt' => 'PT',
                    'kkp' => 'KKP',
                    default => $state,
                }),
            Tables\Columns\TextColumn::make('invoice_date')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('due_date')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('invoice_type')
                ->label('Type')
                ->getStateUsing(fn ($record) => $record->invoice_type)
                ->formatStateUsing(fn ($state) => match (strtolower($state ?? '')) {
                    'pt' => 'PT',
                    'kkp' => 'KKP',
                    default => $state,
                }),
            Tables\Columns\TextColumn::make('invoice_status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'paid' => 'success',
                    'unpaid' => 'warning',
                    'overdue' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            Tables\Columns\TextColumn::make('rek_transfer')
                ->label('Rekening Transfer'),
            Tables\Columns\TextColumn::make('is_send_invoice')
                ->label('Status Kirim Invoice')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    '1' => 'success',
                    '0' => 'warning',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => $state == '1' ? 'Sudah' : 'Belum'),
            Tables\Columns\TextColumn::make('total_amount')
                ->label('Total Amount')
                ->alignEnd()
                ->formatStateUsing(function ($state) {
                    return number_format((float) $state, 0, ',', '.');
                })
                ->getStateUsing(function ($record) {
                    return $record->costListInvoices()->sum('amount') ?? 0;
                })
                ->summarize(
                    Tables\Columns\Summarizers\Summarizer::make()
                        ->label('Total')
                        ->using(function ($query) {
                            $invoiceIds = $query->pluck('id')->toArray();
                            return CostListInvoice::query()->whereIn('invoice_id', $invoiceIds)->sum('amount');
                        })
                        ->formatStateUsing(function ($state) {
                            return 'IDR '.number_format((float) $state, 0, ',', '.');
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
        ];
    }

    private static function getTableFilters(): array
    {
        return [
            Tables\Filters\Filter::make('date_range')
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('year')
                                ->label('Year')
                                ->options(
                                    Invoice::query()
                                        ->whereNotNull('memo_id')
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
                                        return [$month => Carbon::create()->month($month)->format('F')];
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
                        $monthName = Carbon::create()->month($data['month'])->format('F');
                        $indicators[] = "Month: {$monthName}";
                    }
                    if ($data['year'] ?? null) {
                        $indicators[] = "Year: {$data['year']}";
                    }
                    return count($indicators) ? implode(' + ', $indicators) : null;
                }),
            Tables\Filters\SelectFilter::make('invoice_type')
                ->label('Type')
                ->options([
                    'pt' => 'PT',
                    'kkp' => 'KKP',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['value'],
                            fn (Builder $query, $type): Builder => $query->where('invoice_type', $type),
                        );
                }),
            Tables\Filters\SelectFilter::make('invoice_status')
                ->label('Status')
                ->options([
                    'unpaid' => 'Unpaid',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                ]),
        ];
    }

    private static function getTableActions(): array
    {
        return [
            Tables\Actions\EditAction::make()->color('warning'),
            Tables\Actions\Action::make('viewCostList')
                ->label('Detail')
                ->url(fn ($record) => "/admin/invoices/{$record->id}/cost-list")
                ->icon('heroicon-o-eye')
                ->color('info'),
            Tables\Actions\Action::make('previewPdf')
                ->label('Preview PDF')
                ->url(fn ($record) => route('invoices.preview', $record->id))
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->openUrlInNewTab(),
            Tables\Actions\Action::make('updateStatusBayar')
                ->label('Update Status Bayar')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Update Status Bayar')
                ->modalDescription('Pilih rekening transfer untuk menandai invoice sebagai Paid.')
                ->form([
                    Forms\Components\DatePicker::make('tgl_transfer')
                        ->label('Tanggal Transfer')
                        ->default(now())
                        ->required(),
                    Forms\Components\Select::make('rek_transfer')
                        ->label('Rekening Transfer')
                        ->options([
                            'BCA PT' => 'BCA PT',
                            'BCA 425' => 'BCA 425',
                            'BCA 140' => 'BCA 140',
                            'BCA 436' => 'BCA 436',
                            'MANDIRI' => 'MANDIRI',
                            'KAS BESAR' => 'KAS BESAR',
                            'KAS KECIL' => 'KAS KECIL',
                        ])
                        ->required(),
                ])
                ->action(function (Invoice $record, array $data): void {
                    $rekTransferMapping = [
                        'BCA PT' => 1,
                        'BCA 425' => 2,
                        'BCA 140' => 3,
                        'BCA 436' => 4,
                        'MANDIRI' => 5,
                        'KAS BESAR' => 6,
                        'KAS KECIL' => 7,
                    ];

                    $cashReferenceId = $rekTransferMapping[$data['rek_transfer']];
                    $transferDate = Carbon::parse($data['tgl_transfer']);
                    $nextSortOrder = (CashReport::query()->where('cash_reference_id', $cashReferenceId)
                        ->whereYear('transaction_date', $transferDate->year)
                        ->whereMonth('transaction_date', $transferDate->month)
                        ->max('sort_order') ?? 0) + 1;

                    $record->update([
                        'invoice_status' => 'paid',
                        'rek_transfer' => $data['rek_transfer'],
                        'tgl_transfer' => $data['tgl_transfer'],
                    ]);

                    $firstCashReportId = null;
                    $costListInvoices = $record->costListInvoices()->get();
                    foreach ($costListInvoices as $costItem) {
                        $cashReport = CashReport::create([
                            'description' => ($record->client_name ?: '').' - '.$costItem->description.' - '.$record->invoice_number,
                            'cash_reference_id' => $cashReferenceId,
                            'mou_id' => null,
                            'coa_id' => $costItem->coa_id,
                            'invoice_id' => $record->id,
                            'cost_list_invoice_id' => $costItem->id,
                            'type' => 'debit',
                            'debit_amount' => $costItem->amount,
                            'credit_amount' => 0,
                            'transaction_date' => $data['tgl_transfer'],
                            'sort_order' => $nextSortOrder,
                        ]);

                        $nextSortOrder++;

                        if ($firstCashReportId === null) {
                            $firstCashReportId = $cashReport->id;
                        }
                    }

                    if ($firstCashReportId) {
                        $record->update(['cash_report_id' => $firstCashReportId]);
                    }

                    Notification::make()
                        ->title('Status invoice berhasil diubah menjadi Paid')
                        ->success()
                        ->send();
                })
                ->visible(fn (Invoice $record): bool => $record->invoice_status !== 'paid'),
            Tables\Actions\DeleteAction::make()->color('danger'),
        ];
    }

    private static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoiceMemos::route('/'),
            'create' => Pages\CreateInvoiceMemo::route('/create'),
            'edit' => Pages\EditInvoiceMemo::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('memo_id')
            ->whereYear('created_at', date('Y'))
            ->latest('created_at');
    }

    public static function generateInvoiceNumber(Forms\Set $set, Forms\Get $get): void
    {
        $memoId = $get('memo_id');
        $invoiceDate = $get('invoice_date');
        $isSaldoAwal = $get('is_saldo_awal') ?? false;

        if (! $memoId || ! $invoiceDate) {
            return;
        }

        $memo = Memo::query()->find($memoId);
        if (! $memo) {
            return;
        }

        // 1. Type
        $invoiceType = $get('invoice_type') ?? ($memo->tipe_klien ? strtolower($memo->tipe_klien) : 'pt');
        $typeCode = ($invoiceType === 'pt') ? 'PT' : 'KKP';

        // 2. Category (Default to LN for Memos)
        $categoryCode = 'LN';

        // 3. Date
        $date = Carbon::parse($invoiceDate);
        $year = $date->year;
        $month = $date->month;

        $romanMonths = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];
        $monthRoman = $romanMonths[$month];

        // 4. Sequence
        $lastNumber = 0;

        $invoices = Invoice::query()->whereYear('invoice_date', '=', $year)
            ->whereMonth('invoice_date', '=', $month)
            ->where('invoice_type', '=', $invoiceType)
            ->pluck('invoice_number');

        foreach ($invoices as $inv) {
            $val = 0;
            if (preg_match('/^INV\/(\d+)\//', $inv, $matches)) {
                $val = (int) $matches[1];
            } elseif (preg_match('/^INV\/SA\/(\d+)\//', $inv, $matches)) {
                $val = (int) $matches[1];
            }

            if ($val > $lastNumber) {
                $lastNumber = $val;
            }
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        do {
            if ($isSaldoAwal) {
                $result = sprintf('INV/SA/%s/%s/%s/%s/%s', $newNumber, $typeCode, $categoryCode, $monthRoman, $year);
            } else {
                $result = sprintf('INV/%s/%s/%s/%s/%s', $newNumber, $typeCode, $categoryCode, $monthRoman, $year);
            }

            if (Invoice::query()->where('invoice_number', $result)->exists()) {
                $lastNumber++;
                $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                $exists = true;
            } else {
                $exists = false;
            }
        } while ($exists);

        $set('invoice_number', $result);
    }
}
