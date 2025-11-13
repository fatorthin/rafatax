<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\DaftarAktivaTetap;
use App\Models\DepresiasiAktivaTetap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\DepresiasiAktivaTetapResource\Pages;
use App\Filament\Resources\DepresiasiAktivaTetapResource\RelationManagers;

class DepresiasiAktivaTetapResource extends Resource
{
    protected static ?string $model = DepresiasiAktivaTetap::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Histori Depresiasi Aktiva Tetap';

    protected static ?string $navigationGroup = 'Histori Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('daftar_aktiva_tetap_id')
                    ->label('Aktiva Tetap')
                    ->required()
                    ->searchable()
                    ->options(function () {
                        return DaftarAktivaTetap::all()->mapWithKeys(function ($item) {
                            return [$item->id => $item->deskripsi . ' (Rp ' . number_format($item->harga_perolehan, 0, ',', '.') . ')'];
                        });
                    }),
                Forms\Components\DatePicker::make('tanggal_penyusutan')
                    ->required(),
                Forms\Components\TextInput::make('jumlah_penyusutan')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('daftarAktivaTetap.deskripsi')
                    ->formatStateUsing(function ($record) {
                        return $record->daftarAktivaTetap->deskripsi . ' (' . number_format($record->daftarAktivaTetap->harga_perolehan, 0, ',', '.') . ')';
                    }),
                Tables\Columns\TextColumn::make('tanggal_penyusutan')
                    ->date('d-m-Y'),
                Tables\Columns\TextColumn::make('jumlah_penyusutan')
                    ->numeric()
                    ->alignEnd()
                    ->formatStateUsing(function ($record) {
                        return number_format($record->jumlah_penyusutan, 0, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Sum of Penyusutan')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 2, ',', '.');
                            })
                    ),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('daftar_aktiva_tetap_id')
                    ->label('Aktiva Tetap')
                    ->multiple()
                    ->searchable()
                    ->options(function () {
                        return DaftarAktivaTetap::all()->mapWithKeys(function ($item) {
                            return [$item->id => $item->deskripsi . ' (Rp ' . number_format($item->harga_perolehan, 0, ',', '.') . ')'];
                        });
                    }),
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari',
                        2 => 'Februari',
                        3 => 'Maret',
                        4 => 'April',
                        5 => 'Mei',
                        6 => 'Juni',
                        7 => 'Juli',
                        8 => 'Agustus',
                        9 => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember'
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $month): Builder => $query->whereMonth('tanggal_penyusutan', $month),
                        );
                    }),
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(function () {
                        $years = [];
                        $currentYear = now()->year;
                        for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $year): Builder => $query->whereYear('tanggal_penyusutan', $year),
                        );
                    }),

            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Depresiasi Aktiva Tetap')
                    ->modalWidth('xl'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
                Tables\Actions\ForceDeleteAction::make()
                    ->requiresConfirmation(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ManageDepresiasiAktivaTetaps::route('/'),
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
