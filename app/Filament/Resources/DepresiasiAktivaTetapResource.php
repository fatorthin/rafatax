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

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('daftar_aktiva_tetap_id')
                    ->required()
                    ->options(DaftarAktivaTetap::all()->pluck('deskripsi', 'id')),
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
                        return $record->daftarAktivaTetap->deskripsi.' ('.$record->daftarAktivaTetap->harga_perolehan.')';
                    }),
                Tables\Columns\TextColumn::make('tanggal_penyusutan')
                    ->date('d-m-Y'),
                Tables\Columns\TextColumn::make('jumlah_penyusutan')
                    ->numeric()
                    ->alignEnd()
                    ->formatStateUsing(function ($record) {
                        return number_format($record->jumlah_penyusutan, 0, ',', '.');
                    }),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListDepresiasiAktivaTetaps::route('/'),
            'create' => Pages\CreateDepresiasiAktivaTetap::route('/create'),
            'edit' => Pages\EditDepresiasiAktivaTetap::route('/{record}/edit'),
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
