<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\StaffResource\Pages;
use App\Filament\App\Resources\StaffResource\RelationManagers;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\HasPermissions;

class StaffResource extends Resource
{
    use HasPermissions;

    protected static ?string $model = Staff::class;

    protected static ?string $navigationGroup = 'HRD';

    protected static ?string $navigationLabel = 'Daftar Staf';

    protected static ?string $navigationIcon = 'heroicon-o-users';

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
                Forms\Components\TextInput::make('no_spk')
                    ->label('No SPK'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('birth_place')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->required(),
                Forms\Components\TextInput::make('no_ktp')
                    ->label('No KTP')
                    ->maxLength(20),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required(),
                Forms\Components\Textarea::make('address')
                    ->label('Alamat'),
                Forms\Components\Select::make('jenjang')
                    ->options([
                        'SMA' => 'SMA/SMK',
                        'D-3' => 'D-3',
                        'D-4' => 'D-4',
                        'S-1' => 'S-1',
                        'S-2' => 'S-2',
                        'S-3' => 'S-3',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('jurusan')
                    ->label('Jurusan')
                    ->maxLength(255),
                Forms\Components\TextInput::make('university')
                    ->label('Asal Universitas')
                    ->maxLength(255),
                Forms\Components\TextInput::make('no_ijazah')
                    ->label('No Ijazah')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('tmt_training')
                    ->label('TMT Training')
                    ->required(),
                Forms\Components\TextInput::make('periode')
                    ->label('Period')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('selesai_training')
                    ->label('Selesai Training')
                    ->required(),
                Forms\Components\Select::make('department_id')
                    ->relationship('departmentReference', 'name')
                    ->label('Bagian')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('position_id')
                    ->relationship('positionReference', 'name')
                    ->label('Jabatan')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('clients_id')
                    ->relationship('clients', 'company_name')
                    ->label('Clients')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Forms\Components\TextInput::make('salary')
                    ->label('Gaji')
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('position_status')
                    ->label('Status Jabatan')
                    ->options([
                        'tetap' => 'Tetap',
                        'plt/kontrak' => 'PLT/Kontrak',
                        'magang' => 'Magang',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('birth_place')
                    ->label('TTL')
                    ->formatStateUsing(fn($record) => $record->birth_place . ', ' . \Carbon\Carbon::parse($record->birth_date)->format('d-m-Y'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('departmentReference.name')
                    ->label('Bagian')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tmt_training')
                    ->label('TMT Training')
                    ->dateTime('d-m-Y'),
                Tables\Columns\TextColumn::make('masa_kerja')
                    ->label('Masa Kerja (bulan)')
                    ->getStateUsing(function ($record) {
                        if (!$record->tmt_training) {
                            return '-';
                        }
                        $start = \Carbon\Carbon::parse($record->tmt_training);
                        $now = \Carbon\Carbon::now();
                        $diff = $start->diff($now);
                        $years = $diff->y;
                        $months = $diff->m;
                        $result = '';
                        if ($years > 0) {
                            $result .= $years . ' tahun ';
                        }
                        $result .= $months . ' bulan';
                        return trim($result);
                    }),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->onColor('success')
                    ->offColor('danger'),
                Tables\Columns\TextColumn::make('clients.company_name')
                    ->label('Clients')
                    ->wrap()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('salary')
                    ->label('Gaji Pokok')
                    ->formatStateUsing(fn($record) => number_format($record->salary, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('positionReference.name')
                    ->label('Jabatan')
                    ->searchable(),
                Tables\Columns\SelectColumn::make('position_status')
                    ->label('Status Jabatan')
                    ->options([
                        'tetap' => 'Tetap',
                        'plt/kontrak' => 'PLT/Kontrak',
                        'magang' => 'Magang',
                    ])
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('is_active')
                    ->label('Staff Aktif')
                    ->query(fn(Builder $query): Builder => $query->where('is_active', true))
                    ->default()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            ])
            ->defaultSort('name', 'asc')
            ->recordUrl(null)
            ->recordAction(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStaff::route('/'),
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
