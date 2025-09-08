<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MouResource\Pages;
use App\Models\MoU;
use App\Models\CostListInvoice;
use App\Traits\HasPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                            ->placeholder('Pilih tanggal mulai'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Berakhir')
                            ->required()
                            ->placeholder('Pilih tanggal berakhir'),
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
                    ->color(fn (string $state): string => match ($state) {
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
                    ->money('IDR')
                    ->getStateUsing(function ($record) {
                        $total = $record->cost_lists()->sum('amount');
                        return 'Rp ' . number_format($total, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_invoice_amount')
                    ->label('Total Invoice')
                    ->money('IDR')
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
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\Action::make('viewCostList')
                    ->label('Detail Biaya')
                    ->url(fn($record) => route('filament.app.resources.mous.cost-list', ['record' => $record]))
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success'),
            ])
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
            'index' => Pages\ListMous::route('/'),
            'create' => Pages\CreateMou::route('/create'),
            'view' => Pages\ViewMou::route('/{record}'),
            'edit' => Pages\EditMou::route('/{record}/edit'),
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
