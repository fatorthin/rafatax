<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use App\Models\DaftarAktivaTetap;
use App\Models\DepresiasiAktivaTetap;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\DaftarAktivaTetapResource\Pages;

class DaftarAktivaTetapResource extends Resource
{
    protected static ?string $model = DaftarAktivaTetap::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Daftar Aktiva Tetap';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    public static function getHeaderActions(): array
    {
        return [
            Action::make('monthly')
                ->label('Laporan Bulanan')
                ->icon('heroicon-o-calendar')
                ->url(fn (): string => static::getUrl('monthly'))
                ->color('success'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('deskripsi')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('tahun_perolehan')
                    ->required(),
                Forms\Components\TextInput::make('harga_perolehan')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('tarif_penyusutan')
                    ->label('Tarif Penyusutan (%)')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',   
                    ])
                    ->default('aktif')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Nama Aktiva')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tahun_perolehan')
                    ->date('M-Y')
                    ->label('Tahun Perolehan')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('harga_perolehan')
                    ->label('Harga Perolehan')
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        return number_format($record->harga_perolehan, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->summarize(Sum::make()
                        ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 0, ',', '.');
                            })
                        ->label('Total Harga Perolehan')),
                Tables\Columns\TextColumn::make('tarif_penyusutan')
                    ->label('Tarif Penyusutan (%)')
                    ->alignCenter()
                    ->formatStateUsing(function ($record) {
                        return number_format($record->tarif_penyusutan, 0, ',', '.') . '%';
                    }),
                Tables\Columns\TextColumn::make('depresiasi_terakhir')
                    ->label('Depresiasi Terakhir')
                    ->alignEnd()
                    ->getStateUsing(function ($record) {
                        return DepresiasiAktivaTetap::where('daftar_aktiva_tetap_id', $record->id)
                            ->orderBy('tanggal_penyusutan', 'desc')
                            ->orderBy('id', 'desc')
                            ->first()?->jumlah_penyusutan ?? 0;
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    }),
                Tables\Columns\TextColumn::make('total_penyusutan')
                    ->label('Total Penyusutan')
                    ->alignEnd()
                    ->getStateUsing(function ($record) {
                        return DepresiasiAktivaTetap::where('daftar_aktiva_tetap_id', $record->id)
                            ->sum('jumlah_penyusutan');
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->using(function ($query) {
                                $aktivaIds = $query->pluck('id');
                                return DepresiasiAktivaTetap::whereIn('daftar_aktiva_tetap_id', $aktivaIds)
                                    ->sum('jumlah_penyusutan');
                            })
                            ->formatStateUsing(function ($state) {
                                return number_format($state, 0, ',', '.');
                            })
                            ->label('Total Penyusutan')
                    ),
                Tables\Columns\TextColumn::make('nilai_buku')
                    ->label('Nilai Buku')
                    ->alignEnd()
                    ->getStateUsing(function ($record) {
                        $totalPenyusutan = DepresiasiAktivaTetap::where('daftar_aktiva_tetap_id', $record->id)
                            ->sum('jumlah_penyusutan');
                        return $record->harga_perolehan - $totalPenyusutan;
                    })
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->using(function ($query) {
                                $aktivaIds = $query->pluck('id');
                                $totalHargaPerolehan = $query->sum('harga_perolehan');
                                $totalPenyusutan = DepresiasiAktivaTetap::whereIn('daftar_aktiva_tetap_id', $aktivaIds)
                                    ->sum('jumlah_penyusutan');
                                return $totalHargaPerolehan - $totalPenyusutan;
                            })
                            ->formatStateUsing(function ($state) {
                                return number_format($state, 0, ',', '.');
                            })
                            ->label('Total Nilai Buku')
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state == 'aktif' ? 'success' : 'danger')
                    ->formatStateUsing(function ($record) {
                        return $record->status == 'aktif' ? 'Aktif' : 'Non Aktif';
                    }), 
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->url(fn(DaftarAktivaTetap $record): string => DaftarAktivaTetapResource::getUrl('detail', ['record' => $record]))
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->paginated(false)
            ->recordUrl(fn(DaftarAktivaTetap $record): string => DaftarAktivaTetapResource::getUrl('detail', ['record' => $record]))
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
            'index' => Pages\ListDaftarAktivaTetaps::route('/'),
            'create' => Pages\CreateDaftarAktivaTetap::route('/create'),
            'edit' => Pages\EditDaftarAktivaTetap::route('/{record}/edit'),
            'detail' => Pages\DetailAktivaTetap::route('/{record}/detail'),
            'monthly' => Pages\ViewDaftarAktivaTetapMonhtly::route('/monthly'),
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
