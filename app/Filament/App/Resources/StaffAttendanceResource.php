<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Staff;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StaffAttendance;
use Filament\Resources\Resource;
use Filament\Forms\Components\Fieldset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\StaffAttendanceResource\Pages;
use Filament\Actions\Action;
use App\Traits\HasPermissions;
use Filament\Forms\Get;
use Closure;

class StaffAttendanceResource extends Resource
{
    use HasPermissions;

    protected static ?string $model = StaffAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'HRD';

    protected static ?string $navigationLabel = 'Presensi Karyawan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('staff_id')
                    ->label('Staff')
                    ->options(Staff::where('is_active', true)->pluck('name', 'id'))
                    ->required(),
                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->native(false)
                    ->displayFormat('d-m-Y')
                    ->format('Y-m-d')
                    ->default(now())
                    ->rules([
                        function (Get $get, ?StaffAttendance $record) {
                            return function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                $staffId = $get('staff_id');
                                if (!$staffId || !$value) {
                                    return;
                                }
                                $query = StaffAttendance::query()
                                    ->where('staff_id', $staffId)
                                    ->whereDate('tanggal', $value);
                                if ($record && $record->exists) {
                                    $query->where('id', '!=', $record->id);
                                }
                                if ($query->exists()) {
                                    $fail('Kehadiran untuk staff ini pada tanggal tersebut sudah ada.');
                                }
                            };
                        },
                    ]),

                Forms\Components\TimePicker::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->required()
                    ->default('00:00'),
                Forms\Components\TimePicker::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->format('H:i')
                    ->required()
                    ->default('00:00')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if (empty($state)) {
                            $set('durasi_lembur', 0);
                            return;
                        }

                        // Buat objek waktu untuk jam pulang dan batas lembur
                        $today = now()->format('Y-m-d');
                        $jamPulang = \Carbon\Carbon::parse($today . ' ' . $state);
                        $batasLembur = \Carbon\Carbon::parse($today . ' 17:30');

                        // Jika pulang setelah batas lembur
                        if ($jamPulang->greaterThan($batasLembur)) {
                            // Hitung selisih dalam menit
                            $selisihMenit = $jamPulang->diffInMinutes($batasLembur);
                            // Konversi ke jam dengan 1 angka desimal dan pastikan positif
                            $durasiLembur = abs(round($selisihMenit / 60, 1));
                            $set('durasi_lembur', $durasiLembur);
                        } else {
                            $set('durasi_lembur', 0);
                        }
                    }),
                Forms\Components\Select::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'masuk' => 'Masuk',
                        'sakit' => 'Sakit',
                        'izin' => 'Izin',
                        'cuti' => 'Cuti',
                        'alfa' => 'Alfa',
                        'halfday' => 'Tengah Hari',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('durasi_lembur')
                    ->label('Durasi Lembur')
                    ->suffix('Jam')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('visit_solo_count')
                    ->label('Visit Solo')
                    ->numeric()
                    ->suffix('Kali')
                    ->default(0),
                Forms\Components\TextInput::make('visit_luar_solo_count')
                    ->label('Visit Luar Solo')
                    ->numeric()
                    ->suffix('Kali')
                    ->default(0),
                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->maxLength(255),
                Forms\Components\Checkbox::make('is_late')
                    ->label('Terlambat')
                    ->default(false),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->formatStateUsing(function ($state) {
                        return \Carbon\Carbon::parse($state)->locale('id')->translatedFormat('l, d M Y');
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->dateTime('H:i')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->dateTime('H:i')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi_lembur')
                    ->label('Durasi Lembur')
                    ->alignCenter()
                    ->suffix(' Jam')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Kehadiran')
                    ->alignCenter()
                    ->badge()
                    ->color(function ($state) {
                        if ($state == 'masuk') {
                            return 'success';
                        } elseif ($state == 'sakit') {
                            return 'primary';
                        } elseif ($state == 'izin') {
                            return 'warning';
                        } elseif ($state == 'cuti') {
                            return 'info';
                        } elseif ($state == 'alfa') {
                            return 'danger';
                        } elseif ($state == 'halfday') {
                            return 'warning';
                        }
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state == 'masuk') {
                            return 'Masuk';
                        } elseif ($state == 'sakit') {
                            return 'Sakit';
                        } elseif ($state == 'izin') {
                            return 'Izin';
                        } elseif ($state == 'cuti') {
                            return 'Cuti';
                        } elseif ($state == 'alfa') {
                            return 'Alfa';
                        } elseif ($state == 'halfday') {
                            return 'Tengah Hari';
                        }
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_late')
                    ->label('Terlambat')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(function ($state) {
                        return $state ? 'danger' : 'success';
                    })
                    ->formatStateUsing(function ($state) {
                        return $state ? 'Ya' : 'Tidak';
                    }),
                Tables\Columns\TextColumn::make('visit_solo_count')
                    ->label('Visit Solo')
                    ->alignCenter()
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('visit_luar_solo_count')
                    ->label('Visit Luar Solo')
                    ->alignCenter()
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->wrap()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffAttendances::route('/'),
            'create' => Pages\CreateStaffAttendance::route('/create'),
            'edit' => Pages\EditStaffAttendance::route('/{record}/edit'),
            'view-attendance-monthly' => Pages\ViewAttendanceMonthly::route('/monthly'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getHeaderActions(): array
    {
        return [
            Action::make('view-attendance-monthly')
                ->label('Laporan Presensi Bulanan')
                ->url(fn(): string => static::getUrl('view-attendance-monthly'))
                ->color('success'),
        ];
    }
}
