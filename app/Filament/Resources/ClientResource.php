<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ClientResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ->label('Kode Klien')
                    ->required()
                    ->readOnly()
                    ->helperText('Kode akan otomatis dibuat setelah memilih Jenis Klien.')
                    ->unique(ignoreRecord: true)
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('regenerateCode')
                            ->icon('heroicon-m-arrow-path')
                            ->color('info')
                            ->tooltip('Regenerate Code')
                            ->action(function (Forms\Get $get, Forms\Set $set) {
                                $type = $get('type');
                                if (! $type) {
                                    return;
                                }

                                $prefix = match ($type) {
                                    'pt' => 'PT',
                                    'kkp' => 'AO',
                                    default => null,
                                };

                                if (! $prefix) {
                                    return;
                                }

                                $lastClient = Client::where('type', $type)
                                    ->where('code', 'like', "{$prefix}-%")
                                    ->orderByRaw("CAST(SUBSTRING_INDEX(code, '-', -1) AS UNSIGNED) DESC")
                                    ->first();

                                $number = 1;

                                if ($lastClient) {
                                    $parts = explode('-', $lastClient->code);
                                    $lastNumber = (int) end($parts);
                                    $number = $lastNumber + 1;
                                }

                                $set('code', sprintf('%s-%03d', $prefix, $number));
                            })
                    ), // Ignore current record on edit
                Forms\Components\TextInput::make('company_name')
                    ->label('Nama Perusahaan')
                    ->required()
                    ->unique(ignoreRecord: true), // Ignore current record on edit
                Forms\Components\Textarea::make('address')
                    ->label('Alamat Perusahaan')
                    ->rows(3),
                Forms\Components\TextInput::make('contact_person')->required()
                    ->label('Contact Person'),
                Forms\Components\TextInput::make('phone')
                    ->label('No WA Contact Person')
                    ->tel()
                    ->required(),
                Forms\Components\TextInput::make('owner_name')
                    ->label('Nama Pimpinan')
                    ->required(),
                Forms\Components\TextInput::make('owner_role')
                    ->label('Jabatan Pimpinan')
                    ->required(),
                Forms\Components\TextInput::make('npwp')
                    ->label('No NPWP')
                    ->numeric()
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
                    ]),
                Forms\Components\Select::make('jenis_wp')
                    ->label('Jenis WP')
                    ->options([
                        'perseorangan' => 'Perseorangan',
                        'badan' => 'Badan'
                    ])
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Jenis Klien')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP'
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                        if (! $state) {
                            return;
                        }

                        $prefix = match ($state) {
                            'pt' => 'PT',
                            'kkp' => 'AO',
                            default => null,
                        };

                        if (! $prefix) {
                            return;
                        }

                        $lastClient = Client::where('type', $state)
                            ->where('code', 'like', "{$prefix}-%")
                            ->orderByRaw("CAST(SUBSTRING_INDEX(code, '-', -1) AS UNSIGNED) DESC")
                            ->first();

                        $number = 1;

                        if ($lastClient) {
                            $parts = explode('-', $lastClient->code);
                            $lastNumber = (int) end($parts);
                            $number = $lastNumber + 1;
                        }

                        $set('code', sprintf('%s-%03d', $prefix, $number));
                    }),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'pending' => 'Pending',
                        'batal' => 'Batal'
                    ])
                    ->default('pending')
                    ->required(),
                Forms\Components\Select::make('staff')
                    ->label('Staff Penanggung Jawab')
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
                            ->label('PPh Pasal 25 Reporting'),
                        Forms\Components\Toggle::make('pph_23_reporting')
                            ->default(false)
                            ->label('PPh Pasal 23 Reporting'),
                        Forms\Components\Toggle::make('pph_21_reporting')
                            ->default(false)
                            ->label('PPh Pasal 21/Basil Reporting'),
                        Forms\Components\Toggle::make('pph_4_reporting')
                            ->default(false)
                            ->label('PPh Pasal 4 Reporting'),
                        Forms\Components\Toggle::make('ppn_reporting')
                            ->default(false)
                            ->label('PPN Reporting'),
                        Forms\Components\Toggle::make('spt_reporting')
                            ->default(false)
                            ->label('SPT Tahunan Reporting'),
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
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('owner_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('owner_role')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contact_person')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('npwp')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('jenis_wp')
                    ->label('Jenis WP')
                    ->formatStateUsing(fn($state) => $state === 'op' ? 'Perseorangan' : 'Badan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('grade')
                    ->searchable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => $state === 'pt' ? 'PT' : 'KKP'),
                Tables\Columns\IconColumn::make('pph_25_reporting')
                    ->label('PPh 25 Reporting')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('pph_23_reporting')
                    ->label('PPh 23 Reporting')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('pph_21_reporting')
                    ->label('PPh 21 Reporting')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('pph_4_reporting')
                    ->label('PPh 4 Reporting')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('ppn_reporting')
                    ->label('PPN Reporting')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('spt_reporting')
                    ->label('SPT Tahunan Reporting')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->badge()
                    ->color(fn($state) => $state === 'active' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('deleted_at')
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
                Tables\Filters\SelectFilter::make('jenis_wp')
                    ->options([
                        'perseorangan' => 'Perseorangan',
                        'badan' => 'Badan'
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
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn($record) => "/admin/clients/{$record->id}/detail")
                    ->color('info'),
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
            'detail' => Pages\ClientDetail::route('/{record}/detail'),
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
