<?php

namespace App\Filament\App\Resources;

use App\Models\Coa;
use App\Models\MoU;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Traits\HasPermissions;
use App\Models\CostListInvoice;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use App\Filament\App\Resources\MouResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MouResource extends Resource
{
    use HasPermissions;
    protected static ?string $model = MoU::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Daftar MoU';

    protected static ?string $modelLabel = 'MoU';

    protected static ?string $pluralModelLabel = 'Daftar MoU';

    /**
     * Control sidebar visibility for this resource based on permissions.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * Guard list page access for non-authorized users.
     */
    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi MoU')
                    ->schema([
                        Forms\Components\TextInput::make('mou_number')
                            ->label('Nomor MoU')
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                return $rule->whereNull('deleted_at');
                            })
                            ->readOnly()
                            ->required()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('regenerate')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        self::generateMouNumber($set, $get);
                                    })
                            )
                            ->maxLength(255)
                            ->placeholder('Nomor MoU akan otomatis dibuat'),
                        Forms\Components\TextInput::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('Masukkan deskripsi MoU'),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->placeholder('Pilih tanggal mulai')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(date('Y') . '-01-01')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                self::generateMouNumber($set, $get);
                            }),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Berakhir')
                            ->required()
                            ->placeholder('Pilih tanggal berakhir')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(date('Y') . '-12-31'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Kategori dan Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'approved' => 'Disetujui',
                                'unapproved' => 'Belum Disetujui',
                            ])
                            ->default('unapproved')
                            ->required()
                            ->placeholder('Pilih status'),
                        Forms\Components\Radio::make('type')
                            ->label('Tipe')
                            ->options([
                                'pt' => 'PT',
                                'kkp' => 'KKP',
                            ])
                            ->default('pt')
                            ->inline()
                            ->inlineLabel(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                self::generateMouNumber($set, $get);
                            }),
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'company_name')
                            ->searchable(['company_name', 'code'])
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->company_name} - {$record->code}")
                            ->required(),
                        Forms\Components\Select::make('category_mou_id')
                            ->label('Kategori MoU')
                            ->relationship('categoryMou', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih kategori')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                self::generateMouNumber($set, $get);
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informasi Tambahan')
                    ->schema([
                        Forms\Components\TextInput::make('percentage_restitution')
                            ->label('Persentase Restitusi (Opsional)')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->placeholder('0')
                            ->helperText('Masukkan persentase restitusi jika ada'),
                        Forms\Components\TextInput::make('tahun_pajak')
                            ->label('Tahun Pajak')
                            ->numeric()
                            ->default(date('Y'))
                            ->placeholder(date('Y')),
                        Forms\Components\TextInput::make('link_mou')
                            ->label('Link MoU')
                            ->placeholder('Masukkan link MoU'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Rincian Biaya')
                    ->schema([
                        Forms\Components\Repeater::make('cost_lists')
                            ->label('Daftar Biaya')
                            ->relationship('cost_lists')
                            ->schema([
                                Forms\Components\Select::make('coa_id')
                                    ->label('CoA')
                                    ->options(Coa::where('group_coa_id', '40')->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan([
                                        'md' => 4,
                                    ]),

                                Forms\Components\TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->placeholder('Deskripsi biaya')
                                    ->columnSpan([
                                        'md' => 4,
                                    ]),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $price = $get('amount');
                                        $set('total_amount', floatval($state) * floatval($price));
                                    })
                                    ->columnSpan([
                                        'md' => 1,
                                    ]),
                                Forms\Components\TextInput::make('satuan_quantity')
                                    ->label('Satuan')
                                    ->columnSpan([
                                        'md' => 1,
                                    ]),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->placeholder('0')
                                    ->placeholder('0')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $qty = $get('quantity') ?? 1;
                                        $set('total_amount', floatval($state) * floatval($qty));
                                    })
                                    ->columnSpan([
                                        'md' => 3,
                                    ]),
                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->columnSpan([
                                        'md' => 3,
                                    ]),
                            ])
                            ->columns([
                                'md' => 12,
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Tambah Biaya'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('mou_number')
                    ->label('Nomor MoU')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Tanggal Berakhir')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->searchable()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                        default => $state,
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Nama Klien')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('categoryMou.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'unapproved' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'approved' => 'Disetujui',
                        'unapproved' => 'Belum Disetujui',
                        default => $state,
                    })
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_mou_amount')
                    ->label('Total Nilai MoU')
                    ->numeric(locale: 'id')
                    ->getStateUsing(function ($record) {
                        $total = $record->cost_lists()->sum('total_amount');
                        return 'Rp ' . number_format($total, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_invoice_amount')
                    ->label('Total Invoice')
                    ->numeric(locale: 'id')
                    ->getStateUsing(function ($record) {
                        $total = CostListInvoice::where('mou_id', $record->id)
                            ->whereNotNull('invoice_id')
                            ->sum('amount');
                        return 'Rp ' . number_format($total, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'approved' => 'Disetujui',
                        'unapproved' => 'Belum Disetujui',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    ]),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'company_name')
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('category_mou_id')
                    ->label('Category')
                    ->relationship('categoryMou', 'name')
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('month')
                    ->label('Month')
                    ->options(
                        collect(range(1, 12))->mapWithKeys(function ($month) {
                            return [$month => \Carbon\Carbon::create()->month($month)->format('F')];
                        })->toArray()
                    )
                    ->query(fn(Builder $query, $data) => $query->when(
                        $data['value'],
                        fn(Builder $query, $month) => $query->whereMonth('start_date', $month)
                    )),
                Tables\Filters\SelectFilter::make('year')
                    ->label('Year')
                    ->options(
                        MoU::query()
                            ->selectRaw('YEAR(start_date) as year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray()
                    )
                    ->query(fn(Builder $query, $data) => $query->when(
                        $data['value'],
                        fn(Builder $query, $year) => $query->whereYear('start_date', $year)
                    )),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->modalWidth('7xl'),
                Tables\Actions\Action::make('viewCostList')
                    ->label('Detail Biaya')
                    ->url(fn($record) => route('filament.app.resources.mous.cost-list', ['record' => $record]))
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success'),
                Tables\Actions\DeleteAction::make()
            ], position: ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Data'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ManageMous::route('/'),
            'view' => Pages\ViewMou::route('/{record}'),
            'cost-list' => Pages\ListCostMou::route('/{record}/cost-list'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->latest('created_at');
    }

    public static function generateMouNumber(Forms\Set $set, Forms\Get $get): void
    {
        $type = $get('type');
        $categoryId = $get('category_mou_id');
        $startDate = $get('start_date');

        if (!$type || !$categoryId || !$startDate) {
            return;
        }

        // 1. Type
        $typeCode = $type === 'pt' ? 'PT' : 'KKP';

        // 2. Category Code
        $category = \App\Models\CategoryMou::find($categoryId);
        if (!$category) {
            return;
        }

        $categoryName = $category->name;
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

        // 3. Date (Month Roman/Year)
        $date = \Carbon\Carbon::parse($startDate);
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

        // 4. Sequence Number
        $lastNumber = 0;
        $mous = MoU::whereYear('start_date', $year)
            ->where('type', $type)
            ->pluck('mou_number');

        foreach ($mous as $num) {
            if (preg_match('/^(\d+)\//', $num, $matches)) {
                $val = (int)$matches[1];
                if ($val > $lastNumber) {
                    $lastNumber = $val;
                }
            }
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        // Result
        $result = sprintf('%s/%s/%s/%s/%s', $newNumber, $typeCode, $categoryCode, $monthRoman, $year);
        $set('mou_number', $result);
    }
}
