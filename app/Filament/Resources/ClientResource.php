<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Staff;
use Illuminate\Validation\Rule; // Import Rule class

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Daftar Klien';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true), // Ignore current record on edit
                Forms\Components\TextInput::make('company_name')
                    ->required()
                    ->unique(ignoreRecord: true), // Ignore current record on edit
                Forms\Components\TextInput::make('address')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email(),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required(),
                Forms\Components\TextInput::make('owner_name')
                    ->required(),
                Forms\Components\TextInput::make('contact_person'),
                Forms\Components\TextInput::make('npwp')
                    ->required(),
                Forms\Components\Select::make('grade')
                    ->options([
                        'A' => 'A',
                        'B' => 'B',
                        'C1' => 'C1',
                        'C2' => 'C2',
                        'D1' => 'D1',
                        'D2' => 'D2',
                        'E' => 'E',
                        'F' => 'F',
                        'G' => 'G',
                        'H' => 'H',
                        'I' => 'I',
                    ])
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP'
                    ])
                    ->required(),
                Forms\Components\Select::make('staff')
                    ->relationship('staff', 'name') // 'staff' adalah nama relasi, 'name' adalah kolom yang ditampilkan
                    ->multiple() // Memungkinkan pemilihan banyak staff
                    ->preload() // Opsional: memuat semua opsi di awal
                    ->searchable(), // Opsional: memungkinkan pencarian staff
                Forms\Components\Fieldset::make('Reporting')
                    ->label('Reporting Options')
                    ->columns(3) // Atur jumlah kolom sesuai kebutuhan
                    ->schema([
                        Forms\Components\Toggle::make('pph_25_reporting')
                            ->default(false)
                            ->label('PPh 25 Reporting'),
                        Forms\Components\Toggle::make('pph_21_reporting')
                            ->default(false)
                            ->label('PPh 21 Reporting'),
                        Forms\Components\Toggle::make('ppn_reporting')
                            ->default(false)
                            ->label('PPN Reporting'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('owner_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contact_person')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('npwp')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('grade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\IconColumn::make('pph_25_reporting')
                    ->label('PPh 25 Reporting')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('pph_21_reporting')
                    ->label('PPh 21 Reporting')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('ppn_reporting')
                    ->label('PPN Reporting')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable(),
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
                Tables\Filters\SelectFilter::make('grade')
                    ->options([
                        'A' => 'A',
                        'B' => 'B',
                        'C1' => 'C1',
                        'C2' => 'C2',
                        'D1' => 'D1',
                        'D2' => 'D2',
                        'E' => 'E',
                        'F' => 'F',
                        'G' => 'G',
                        'H' => 'H',
                        'I' => 'I',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP'
                    ]),
                Tables\Filters\SelectFilter::make('pph_25_reporting')
                    ->options([
                        true => 'Yes',
                        false => 'No',
                    ]),
                Tables\Filters\SelectFilter::make('pph_21_reporting')
                    ->options([
                        true => 'Yes',
                        false => 'No',
                    ]),
                Tables\Filters\SelectFilter::make('ppn_reporting')
                    ->options([
                        true => 'Yes',
                        false => 'No',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
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
