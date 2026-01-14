<?php

namespace App\Filament\App\Resources;

use App\Models\MoU;
use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Traits\HasPermissions;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\InvoiceResource\Pages;
use App\Filament\App\Resources\InvoiceResource\RelationManagers;

class InvoiceResource extends Resource
{
    use HasPermissions;

    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Daftar Invoice';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Radio::make('reference_type')
                    ->label('Reference Type')
                    ->options([
                        'mou' => 'MoU',
                        'memo' => 'Memo',
                    ])
                    ->default('mou')
                    ->inline()
                    ->live()
                    ->afterStateHydrated(function (Forms\Set $set, $record) {
                        if ($record?->memo_id) {
                            $set('reference_type', 'memo');
                        } else {
                            $set('reference_type', 'mou');
                        }
                    })
                    ->afterStateUpdated(function (Forms\Set $set) {
                        $set('mou_id', null);
                        $set('memo_id', null);
                        $set('invoice_number', null);
                    }),
                Forms\Components\Select::make('mou_id')
                    ->label('MoU')
                    ->options(function () {
                        return MoU::query()
                            ->select('id', 'mou_number', 'description', 'client_id')
                            ->with('client')
                            ->get()
                            ->mapWithKeys(function ($mou) {
                                return [$mou->id => ($mou->client->company_name ?? '-') . ' - ' . $mou->mou_number . ' - ' . $mou->description];
                            });
                    })
                    ->searchable()
                    ->required(fn(Forms\Get $get) => $get('reference_type') === 'mou')
                    ->visible(fn(Forms\Get $get) => $get('reference_type') === 'mou')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $mou = MoU::find($state);
                            if ($mou) {
                                $set('invoice_type', $mou->type);
                            }
                        }
                        self::generateInvoiceNumber($set, $get);
                    }),
                Forms\Components\Select::make('memo_id')
                    ->label('Memo')
                    ->options(function () {
                        return \App\Models\Memo::query()
                            ->select('id', 'no_memo', 'description')
                            ->get()
                            ->mapWithKeys(function ($memo) {
                                return [$memo->id => $memo->no_memo . ' - ' . $memo->description];
                            });
                    })
                    ->searchable()
                    ->required(fn(Forms\Get $get) => $get('reference_type') === 'memo')
                    ->visible(fn(Forms\Get $get) => $get('reference_type') === 'memo')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        self::generateInvoiceNumber($set, $get);
                    }),
                Forms\Components\TextInput::make('invoice_number')
                    ->required()
                    ->maxLength(255)
                    ->readOnly()
                    ->unique(
                        Invoice::class,
                        'invoice_number',
                        fn($record) => $record,
                        modifyRuleUsing: function ($rule) {
                            return $rule->whereNull('deleted_at');
                        }
                    )
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('refresh_invoice_number')
                            ->icon('heroicon-o-arrow-path')
                            ->tooltip('Regenerate Invoice Number')
                            ->action(function (Forms\Set $set, Forms\Get $get) {
                                self::generateInvoiceNumber($set, $get);
                            })
                    ),
                Forms\Components\DatePicker::make('invoice_date')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            // Add 3 weeks to the invoice date
                            $dueDate = date('Y-m-d', strtotime($state . ' + 3 weeks'));
                            $set('due_date', $dueDate);
                        }
                        self::generateInvoiceNumber($set, $get);
                    }),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                Forms\Components\Select::make('invoice_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid'
                    ]),
                Forms\Components\Select::make('invoice_type')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP'
                    ])
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        self::generateInvoiceNumber($set, $get);
                    }),
                Forms\Components\Checkbox::make('is_saldo_awal')
                    ->label('Checklist Invoice Saldo Awal')
                    ->default(false)
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        self::generateInvoiceNumber($set, $get);
                    }),
                Forms\Components\Section::make('Rincian Biaya')
                    ->schema([
                        Forms\Components\Repeater::make('costListInvoices')
                            ->relationship()
                            ->schema([
                                Forms\Components\Hidden::make('mou_id')
                                    ->default(fn(Forms\Get $get) => $get('../../mou_id')),
                                Forms\Components\Select::make('coa_id')
                                    ->label('CoA')
                                    ->options(\App\Models\Coa::where('group_coa_id', '40')->orWhere('id', '162')->pluck('name', 'id'))
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
                            ->itemLabel(fn(array $state): ?string => $state['description'] ?? null),
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
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('MoU / Memo Number')
                    ->getStateUsing(fn($record) => $record->mou?->mou_number ?? $record->memo?->no_memo)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('mou', fn($q) => $q->where('mou_number', 'like', "%{$search}%"))
                            ->orWhereHas('memo', fn($q) => $q->where('no_memo', 'like', "%{$search}%"));
                    }),
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Client')
                    ->getStateUsing(fn($record) => $record->mou?->client?->company_name ?? $record->memo?->nama_klien)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('mou.client', fn($q) => $q->where('company_name', 'like', "%{$search}%"))
                            ->orWhereHas('memo', fn($q) => $q->where('nama_klien', 'like', "%{$search}%"));
                    }),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\SelectColumn::make('invoice_type')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    ])
                    ->sortable(),
                Tables\Columns\SelectColumn::make('invoice_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                    ])
                    ->sortable(),
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
                                fn(Builder $query, $year): Builder => $query->whereYear('invoice_date', $year),
                            )
                            ->when(
                                $data['month'],
                                fn(Builder $query, $month): Builder => $query->whereMonth('invoice_date', $month),
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
                    ->relationship('mou.client', 'company_name')
                    ->searchable()
                    ->preload(),
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
                                fn(Builder $query, $type): Builder => $query->whereHas('mou', fn($q) => $q->where('type', $type)),
                            );
                    }),
                Tables\Filters\SelectFilter::make('invoice_status')
                    ->label('Status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->color('warning'),
                Tables\Actions\Action::make('viewCostList')
                    ->label('Detail')
                    ->url(fn($record) => static::getUrl('viewCostList', ['record' => $record->id]))
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
            ])
            ->defaultSort('invoice_date', 'desc')
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvoices::route('/'),
            'viewCostList' => Pages\ListCostInvoice::route('/{record}/cost-list'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
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

    public static function generateInvoiceNumber(Forms\Set $set, Forms\Get $get): void
    {
        $mouId = $get('mou_id');
        $memoId = $get('memo_id');
        $invoiceDate = $get('invoice_date');
        $isSaldoAwal = $get('is_saldo_awal') ?? false;

        if ((!$mouId && !$memoId) || !$invoiceDate) {
            return;
        }

        if ($mouId) {
            $mou = MoU::with('categoryMou')->find($mouId);
            if (!$mou) return;

            // 1. Type
            $invoiceType = $get('invoice_type');
            $typeCode = ($invoiceType === 'pt') ? 'PT' : 'KKP';

            // 2. Category
            $categoryName = $mou->categoryMou?->name;
            $categoryCode = match ($categoryName) {
                'Bulanan Perorangan' => 'BTH',
                'Bulanan Perusahaan' => 'BTH',
                'SPT Perorangan' => 'TH',
                'SPT Perusahaan' => 'TH',
                'Pembetulan' => 'PBT',
                'Pembukuan' => 'PBK',
                'Pemeriksaan' => 'PMK',
                'Restitusi' => 'RS',
                'SP2DK' => 'SP',
                'Konsultasi' => 'KS',
                'Keberatan' => 'KB',
                'Pelatihan' => 'PL',
                'Lainnya' => 'LN',
                default => 'LN',
            };
        } elseif ($memoId) {
            $memo = \App\Models\Memo::find($memoId);
            if (!$memo) return;

            // 1. Type
            $invoiceType = $get('invoice_type');
            $typeCode = ($invoiceType === 'pt') ? 'PT' : 'KKP';

            // 2. Category (Default to LN for Memos)
            $categoryCode = 'LN';
        } else {
            return;
        }

        // 3. Date
        $date = \Carbon\Carbon::parse($invoiceDate);
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
            12 => 'XII'
        ];
        $monthRoman = $romanMonths[$month];

        // 4. Sequence
        // Reset to 1 if month changes
        $lastNumber = 0;

        $invoiceType = $get('invoice_type');

        // Find existing invoices for the same month and year AND same type
        // We look for patterns like INV/001... or INV/SA/001...
        $invoices = Invoice::whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
            ->where('invoice_type', $invoiceType)
            ->pluck('invoice_number');

        foreach ($invoices as $inv) {
            $val = 0;
            // Pattern 1: Normal INV/001/...
            if (preg_match('/^INV\/(\d+)\//', $inv, $matches)) {
                $val = (int)$matches[1];
            }
            // Pattern 2: SA INV/SA/001/...
            elseif (preg_match('/^INV\/SA\/(\d+)\//', $inv, $matches)) {
                $val = (int)$matches[1];
            }

            if ($val > $lastNumber) {
                $lastNumber = $val;
            }
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        // Loop to find next available number in case of race condition (check existence)
        do {
            if ($isSaldoAwal) {
                $result = sprintf('INV/SA/%s/%s/%s/%s/%s', $newNumber, $typeCode, $categoryCode, $monthRoman, $year);
            } else {
                $result = sprintf('INV/%s/%s/%s/%s/%s', $newNumber, $typeCode, $categoryCode, $monthRoman, $year);
            }

            if (Invoice::where('invoice_number', $result)->exists()) {
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
