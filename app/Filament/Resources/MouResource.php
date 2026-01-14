<?php

namespace App\Filament\Resources;

use App\Models\MoU;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CostListInvoice;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use App\Filament\Resources\MouResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MouResource extends Resource
{
    protected static ?string $model = MoU::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Daftar MoU';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('mou_number')->label('MoU Number')
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
                    ),
                Forms\Components\TextInput::make('description')
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(date('Y') . '-01-01')
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        self::generateMouNumber($set, $get);
                    }),
                Forms\Components\DatePicker::make('end_date')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(date('Y') . '-12-31'),
                Forms\Components\Select::make('status')
                    ->options([
                        'approved' => 'Approved',
                        'unapproved' => 'Unapproved',
                    ])
                    ->default('unapproved')
                    ->required(),
                Forms\Components\Radio::make('type')
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
                    ->label('Category MoU')
                    ->relationship('categoryMou', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        self::generateMouNumber($set, $get);
                    }),
                Forms\Components\TextInput::make('percentage_restitution')
                    ->label('Percentage Restitution (optional)')
                    ->numeric()
                    ->default(0)
                    ->suffix('%'),
                Forms\Components\TextInput::make('tahun_pajak')
                    ->label('Tahun Pajak')
                    ->numeric()
                    ->default(date('Y')),
                Forms\Components\TextInput::make('link_mou')
                    ->label('Link MoU')
                    ->placeholder('Masukkan link MoU'),
                Forms\Components\Section::make('Cost List Details')
                    ->schema([
                        Forms\Components\Repeater::make('cost_lists')
                            ->relationship('cost_lists')
                            ->schema([
                                Forms\Components\Select::make('coa_id')
                                    ->label('CoA')
                                    ->options(\App\Models\Coa::where('group_coa_id', '40')->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan([
                                        'md' => 4,
                                    ]),
                                Forms\Components\TextInput::make('description')
                                    ->label('Description')
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
                                    ->label('Price')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->required()
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
                            ->defaultItems(0),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('mou_number')->label('MoU Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    }),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('categoryMou.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'approved' => 'Approved',
                        'unapproved' => 'Unapproved',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('cost_lists_sum_amount')
                    ->label('Total MoU Amount')
                    ->numeric(locale: 'id')
                    ->getStateUsing(function ($record) {
                        return $record->cost_lists()->sum('total_amount');
                    })->alignEnd(),
                Tables\Columns\TextColumn::make('total_invoice_amount')
                    ->label('Total Invoice Amount')
                    ->numeric(locale: 'id')
                    ->getStateUsing(function ($record) {
                        return CostListInvoice::where('mou_id', $record->id)
                            ->whereNotNull('invoice_id')
                            ->sum('amount');
                    })->alignEnd(),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'PT' => 'PT',
                        'KKP' => 'KKP',
                    ]),
                Tables\Filters\SelectFilter::make('client_id')
                    ->preload()
                    ->label('Client')
                    ->relationship('client', 'company_name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('category_mou_id')
                    ->label('Category')
                    ->relationship('categoryMou', 'name')
                    ->multiple()
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
                    ->color('info')
                    ->modalWidth('7xl'),
                Tables\Actions\Action::make('viewCostList')
                    ->label('Detail')
                    ->url(fn($record) => "/admin/mous/{$record->id}/cost-list")
                    ->icon('heroicon-o-eye')
                    ->color('success'),
                Tables\Actions\DeleteAction::make(),
            ], position: ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'viewCostList' => Pages\ListCostMou::route('/{record}/cost-list'),
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
