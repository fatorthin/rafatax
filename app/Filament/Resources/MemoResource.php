<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemoResource\Pages;
use App\Filament\Resources\MemoResource\RelationManagers;
use App\Models\Memo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MemoResource extends Resource
{
    protected static ?string $model = Memo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Daftar Memo Kesepakatan';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->label('Deskripsi')
                    ->required(),
                Forms\Components\TextInput::make('nama_klien')
                    ->label('Nama Klien')
                    ->required(),
                Forms\Components\TextInput::make('instansi_klien')
                    ->label('Instansi Klien')
                    ->required(),
                Forms\Components\Textarea::make('alamat_klien')
                    ->label('Alamat Klien')
                    ->required(),
                Forms\Components\Select::make('tipe_klien')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    ])
                    ->default('pt')
                    ->inline()
                    ->inlineLabel(false)
                    ->required(),
                Forms\Components\DateTimePicker::make('tanggal_ttd')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->label('Tanggal TTD')
                    ->required(),
                Forms\Components\Repeater::make('type_work')
                    ->label('Type Work')
                    ->schema([
                        Forms\Components\TextInput::make('work_detail')
                            ->required(),
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi'),
                Tables\Columns\TextColumn::make('nama_klien')
                    ->label('Nama Klien'),
                Tables\Columns\TextColumn::make('instansi_klien')
                    ->label('Instansi Klien'),
                Tables\Columns\TextColumn::make('alamat_klien')
                    ->label('Alamat Klien'),
                Tables\Columns\TextColumn::make('tipe_klien')
                    ->label('Tipe Klien'),
                Tables\Columns\TextColumn::make('tanggal_ttd')
                    ->label('Tanggal TTD'),
                Tables\Columns\TextColumn::make('type_work')
                    ->label('Type Work')
                    ->getStateUsing(function ($record) {
                        return collect($record->type_work ?? [])->pluck('work_detail')->implode(', ');
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMemos::route('/'),
        ];
    }
}
