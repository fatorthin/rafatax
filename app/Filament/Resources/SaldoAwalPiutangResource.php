<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaldoAwalPiutangResource\Pages;
use App\Models\SaldoAwalPiutang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SaldoAwalPiutangResource extends Resource
{
    protected static ?string $model = SaldoAwalPiutang::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Saldo Awal Piutang Klien';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->label('Klien')
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('year')
                    ->label('Periode / Tahun')
                    ->options([
                        2024 => 'Sebelum 2025 (<= 2024)',
                        2025 => 'Tahun 2025',
                        2026 => 'Tahun 2026',
                        2027 => 'Tahun 2027',
                        2028 => 'Tahun 2028',
                    ])
                    ->default(2024)
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                Forms\Components\TextInput::make('notes')
                    ->label('Keterangan')
                    ->placeholder('Contoh: Saldo awal cut-off sebelum 2025')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Nama Klien')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Periode / Tahun')
                    ->badge()
                    ->color(fn($state) => (int)$state < 2025 ? 'gray' : 'info')
                    ->formatStateUsing(fn($state) => (int)$state < 2025 ? "$state (Sebelum 2025)" : "Tahun $state")
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->searchable(),
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
                Tables\Filters\SelectFilter::make('year')
                    ->label('Periode / Tahun')
                    ->options([
                        'pre_2025' => 'Sebelum 2025 (< 2025)',
                        '2025' => 'Tahun 2025',
                        '2026' => 'Tahun 2026',
                        '2027' => 'Tahun 2027',
                        '2028' => 'Tahun 2028',
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        if ($data['value'] === 'pre_2025') {
                            return $query->where('year', '<', 2025);
                        }
                        return $query->where('year', (int) $data['value']);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ManageSaldoAwalPiutangs::route('/'),
        ];
    }
}
