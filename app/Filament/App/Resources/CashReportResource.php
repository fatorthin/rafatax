<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CashReportResource\Pages;
use App\Models\CashReport;
use App\Models\CashReference;
use App\Models\Coa;
use App\Traits\HasPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CashReportResource extends Resource
{
    use HasPermissions;
    protected static ?string $model = CashReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Laporan Kas';
    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $modelLabel = 'Laporan Kas';
    protected static ?string $pluralModelLabel = 'Laporan Kas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('Tanggal Transaksi')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('coa_id')
                            ->label('Chart of Account')
                            ->required()
                            ->searchable()
                            ->options(function () {
                                return Coa::all()->mapWithKeys(function ($coa) {
                                    return [$coa->id => $coa->code . ' - ' . $coa->name];
                                });
                            })
                            ->placeholder('Pilih chart of account'), 
                        Forms\Components\Select::make('cash_reference_id')
                            ->label('Referensi Kas')
                            ->required()
                            ->searchable()
                            ->default(function () {
                                return request()->query('cash_reference_id');
                            })
                            ->options(CashReference::all()->pluck('name', 'id'))
                            ->placeholder('Pilih referensi kas'),
                        Forms\Components\TextInput::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan deskripsi transaksi'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Jumlah Transaksi')
                    ->schema([
                        Forms\Components\TextInput::make('debit_amount')
                            ->label('Debit')
                            ->default(0)
                            ->numeric()
                            ->prefix('Rp ')
                            ->placeholder('0.00')
                            ->minValue(0)
                            ->rules(['required_without:credit_amount'])
                            ->helperText('Harus diisi jika credit kosong'),
                        Forms\Components\TextInput::make('credit_amount')
                            ->label('Credit')
                            ->default(0)
                            ->numeric()
                            ->prefix('Rp ')
                            ->placeholder('0.00')
                            ->minValue(0)
                            ->rules(['required_without:debit_amount'])
                            ->helperText('Harus diisi jika debit kosong'),
                    ])
                    ->columns(2)
                    ->description('Masukkan jumlah debit atau credit (salah satu harus diisi)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('transaction_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('cashReference.name')
                    ->label('Referensi Kas')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('coa.code')
                    ->label('Kode CoA')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('coa.name')
                    ->label('Nama CoA')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('debit_amount')
                    ->label('Debit')
                    ->numeric()
                    ->formatStateUsing(function ($state) {
                        return 'Rp ' . number_format((float)$state, 2, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Debit')
                            ->formatStateUsing(function ($state) {
                                return 'Rp ' . number_format((float)$state, 2, ',', '.');
                            })
                    )
                    ->sortable()
                    ->alignEnd()
                    ->color('success')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('credit_amount')
                    ->label('Credit')
                    ->numeric()
                    ->formatStateUsing(function ($state) {
                        return 'Rp ' . number_format((float)$state, 2, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Credit')
                            ->formatStateUsing(function ($state) {
                                return 'Rp ' . number_format((float)$state, 2, ',', '.');
                            })
                    )
                    ->sortable()
                    ->alignEnd()
                    ->color('danger')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->formatStateUsing(function ($state) {
                        return 'Rp ' . number_format((float)$state, 2, ',', '.');
                    })
                    ->getStateUsing(function ($record, $column) {
                        // Get all cash reports for the same cash reference, ordered by date
                        $cashReports = CashReport::where('cash_reference_id', $record->cash_reference_id)
                            ->where(function ($query) use ($record) {
                                $query->where('transaction_date', '<', $record->transaction_date)
                                    ->orWhere(function ($q) use ($record) {
                                        $q->where('transaction_date', '=', $record->transaction_date)
                                            ->where('id', '<=', $record->id);
                                    });
                            })
                            ->orderBy('transaction_date')
                            ->orderBy('id')
                            ->get();

                        // Calculate running balance
                        $balance = 0;
                        foreach ($cashReports as $report) {
                            $balance += $report->debit_amount - $report->credit_amount;
                        }

                        return $balance;
                    })
                    ->color(function ($state) {
                        return $state >= 0 ? 'success' : 'danger';
                    })
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cash_reference_id')
                    ->label('Referensi Kas')
                    ->relationship('cashReference', 'name'),
                Tables\Filters\SelectFilter::make('coa_id')
                    ->label('Chart of Account')
                    ->relationship('coa', 'name'),
                Tables\Filters\Filter::make('transaction_month')
                    ->label('Bulan')
                    ->form([
                        Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options([
                                '1' => 'Januari',
                                '2' => 'Februari',
                                '3' => 'Maret',
                                '4' => 'April',
                                '5' => 'Mei',
                                '6' => 'Juni',
                                '7' => 'Juli',
                                '8' => 'Agustus',
                                '9' => 'September',
                                '10' => 'Oktober',
                                '11' => 'November',
                                '12' => 'Desember',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['month'],
                                fn(Builder $query, $month): Builder => $query->whereMonth('transaction_date', $month)
                            );
                    }),
                Tables\Filters\Filter::make('transaction_year')
                    ->label('Tahun')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->label('Tahun')
                            ->options(function () {
                                $years = [];
                                $currentYear = now()->year;
                                for ($i = $currentYear - 5; $i <= $currentYear; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year'],
                                fn(Builder $query, $year): Builder => $query->whereYear('transaction_date', $year)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Data'),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc')
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
            'index' => Pages\ListCashReports::route('/'),
            'create' => Pages\CreateCashReport::route('/create'),
            'view' => Pages\ViewCashReport::route('/{record}'),
            'edit' => Pages\EditCashReport::route('/{record}/edit'),
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
