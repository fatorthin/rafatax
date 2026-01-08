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
                Forms\Components\TextInput::make('no_memo')
                    ->label('No Memo')
                    ->required()
                    ->readOnly()
                    ->dehydrated()
                    ->unique(ignoreRecord: true),
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
                Forms\Components\Radio::make('tipe_klien')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    ])
                    ->default('pt')
                    ->live()
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                        self::generateMemoNumber($get, $set);
                    })
                    ->required(),
                Forms\Components\DateTimePicker::make('tanggal_ttd')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->label('Tanggal TTD')
                    ->live()
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                        self::generateMemoNumber($get, $set);
                    })
                    ->required(),
                Forms\Components\TextInput::make('total_fee')
                    ->label('Total Fee')
                    ->numeric()
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
                Tables\Columns\TextColumn::make('no_memo')
                    ->label('No Memo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_klien')
                    ->searchable()
                    ->label('Nama Klien'),
                Tables\Columns\TextColumn::make('instansi_klien')
                    ->searchable()
                    ->label('Instansi Klien'),
                Tables\Columns\TextColumn::make('alamat_klien')
                    ->searchable()
                    ->label('Alamat Klien'),
                Tables\Columns\TextColumn::make('tipe_klien')
                    ->searchable()
                    ->label('Tipe Klien'),
                Tables\Columns\TextColumn::make('tanggal_ttd')
                    ->date('d-m-Y')
                    ->label('Tanggal TTD'),
                Tables\Columns\TextColumn::make('type_work')
                    ->label('Type Work')
                    ->getStateUsing(function ($record) {
                        return collect($record->type_work ?? [])->pluck('work_detail')->implode(', ');
                    }),
                Tables\Columns\TextColumn::make('total_fee')
                    ->numeric(locale: 'id')
                    ->label('Total Fee'),
                Tables\Columns\TextColumn::make('total_invoice_amount')
                    ->label('Total Invoice')
                    ->numeric(locale: 'id')
                    ->getStateUsing(function ($record) {
                        return \App\Models\CostListInvoice::whereHas('invoice', function ($query) use ($record) {
                            $query->where('memo_id', $record->id);
                        })->sum('amount');
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe_klien')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    ])
                    ->label('Tipe Klien'),
                Tables\Filters\Filter::make('tanggal_ttd')
                    ->form([
                        Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options([
                                '01' => 'Januari',
                                '02' => 'Februari',
                                '03' => 'Maret',
                                '04' => 'April',
                                '05' => 'Mei',
                                '06' => 'Juni',
                                '07' => 'Juli',
                                '08' => 'Agustus',
                                '09' => 'September',
                                '10' => 'Oktober',
                                '11' => 'November',
                                '12' => 'Desember',
                            ]),
                        Forms\Components\Select::make('year')
                            ->label('Tahun')
                            ->options(function () {
                                $years = range(date('Y') - 5, date('Y') + 5);
                                return array_combine($years, $years);
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['month'],
                                fn(Builder $query, $month) => $query->whereMonth('tanggal_ttd', $month)
                            )
                            ->when(
                                $data['year'],
                                fn(Builder $query, $year) => $query->whereYear('tanggal_ttd', $year)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            RelationManagers\InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMemos::route('/'),
            'view' => Pages\ViewMemo::route('/{record}'),
        ];
    }

    public static function generateMemoNumber(Forms\Get $get, Forms\Set $set): void
    {
        $tipeKlien = $get('tipe_klien');
        $tanggalTtd = $get('tanggal_ttd');

        if (! $tipeKlien || ! $tanggalTtd) {
            return;
        }

        $date = \Carbon\Carbon::parse($tanggalTtd);
        $year = $date->year;
        $month = $date->month;
        $romanMonth = self::getRomanMonth($month);

        // Try to preserve sequence if exists to prevent jumping numbers on edit
        $currentNo = $get('no_memo');
        $sequence = null;

        if ($currentNo) {
            $parts = explode('/', $currentNo);
            // Format: {seq}/{type}/LN/{roman}/{year}
            if (count($parts) === 5) {
                $oldSequence = $parts[0];
                $oldYear = $parts[4];

                if ($oldYear == $year) {
                    $sequence = $oldSequence;
                }
            }
        }

        if (! $sequence) {
            // Find max sequence from both Memos and MoUs
            $lastNumber = 0;

            // Check Memos
            $memos = Memo::whereYear('tanggal_ttd', $year)->pluck('no_memo');
            foreach ($memos as $num) {
                if (preg_match('/^(\d+)\//', $num, $matches)) {
                    $val = (int)$matches[1];
                    if ($val > $lastNumber) {
                        $lastNumber = $val;
                    }
                }
            }

            // Check MoUs
            $mous = \App\Models\MoU::whereYear('start_date', $year)->pluck('mou_number');
            foreach ($mous as $num) {
                if (preg_match('/^(\d+)\//', $num, $matches)) {
                    $val = (int)$matches[1];
                    if ($val > $lastNumber) {
                        $lastNumber = $val;
                    }
                }
            }

            $sequence = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        }

        // Format: {no urut memo}/{tipe klien}/LN/{bulan dalam romawi}/{tahun}
        $number = sprintf('%s/%s/LN/%s/%s', $sequence, strtoupper($tipeKlien), $romanMonth, $year);

        $set('no_memo', $number);
    }

    protected static function getRomanMonth($month)
    {
        $map = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        return $map[$month] ?? '';
    }
}
