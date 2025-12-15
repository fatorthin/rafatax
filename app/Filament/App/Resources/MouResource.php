<?php

namespace App\Filament\App\Resources;

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
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nomor MoU'),
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
                            ->default(date('Y') . '-01-01'),
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
                            ->default('approved')
                            ->required()
                            ->placeholder('Pilih status'),
                        Forms\Components\Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'pt' => 'PT',
                                'kkp' => 'KKP',
                            ])
                            ->required()
                            ->placeholder('Pilih tipe'),
                        Forms\Components\Select::make('client_id')
                            ->label('Klien')
                            ->relationship('client', 'company_name')
                            ->searchable()
                            ->required()
                            ->placeholder('Pilih klien'),
                        Forms\Components\Select::make('category_mou_id')
                            ->label('Kategori MoU')
                            ->relationship('categoryMou', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih kategori'),
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
                                    ->options(\App\Models\Coa::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan([
                                        'md' => 4,
                                    ]),
                                Forms\Components\TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->required()
                                    ->placeholder('Deskripsi biaya')
                                    ->columnSpan([
                                        'md' => 4,
                                    ]),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->placeholder('0')
                                    ->columnSpan([
                                        'md' => 4,
                                    ]),
                            ])
                            ->columns([
                                'md' => 12,
                            ])
                            ->defaultItems(1)
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
                        $total = $record->cost_lists()->sum('amount');
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
                Tables\Filters\SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(
                        MoU::query()
                            ->selectRaw('YEAR(start_date) as year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray()
                    ),
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
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->latest('created_at');
    }
}
