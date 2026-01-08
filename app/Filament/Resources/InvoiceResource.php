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
    protected static ?string $navigationGroup = 'Bagian Keuangan';
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
                    ->afterStateUpdated(function (Forms\Set $set) {
                        $set('mou_id', null);
                        $set('memo_id', null);
                        $set('invoice_number', null);
                    }),
                Forms\Components\Select::make('mou_id')
                    ->label('MoU')
                    ->options(function () {
                        return MoU::query()
                            ->select('id', 'mou_number', 'description')
                            ->get()
                            ->mapWithKeys(function ($mou) {
                                return [$mou->id => $mou->mou_number . ' - ' . $mou->description];
                            });
                    })
                    ->searchable()
                    ->required(fn(Forms\Get $get) => $get('reference_type') === 'mou')
                    ->visible(fn(Forms\Get $get) => $get('reference_type') === 'mou')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
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
                            // Add 3 weeks to the invoice date
                            $dueDate = date('Y-m-d', strtotime($state . ' + 3 weeks'));
                            $set('due_date', $dueDate);
                        }
                        self::generateInvoiceNumber($set, $get);
                    }),
                Forms\Components\DatePicker::make('due_date')
                    ->required()
                    ->default(now()->addWeeks(3)),
                Forms\Components\Select::make('invoice_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid'
                    ])
                    ->required()
                    ->default('unpaid'),
                Forms\Components\Select::make('invoice_type')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP'
                    ])
                    ->required(),
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
                                    ->options(\App\Models\Coa::where('group_coa_id', '40')->pluck('name', 'id'))
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
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->getStateUsing(fn($record) => $record->mou?->type ?? $record->memo?->tipe_klien)
                    ->formatStateUsing(fn($state) => match (strtolower($state ?? '')) {
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
            ])
            ->defaultSort('invoice_date', 'desc')
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
            $typeCode = $mou->type === 'pt' ? 'PT' : 'KKP';

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
            $typeCode = $memo->tipe_klien === 'pt' ? 'PT' : 'KKP';

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

        // Find existing invoices for the same month and year
        // We look for patterns like INV/001... or INV/SA/001...
        $invoices = Invoice::whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
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
