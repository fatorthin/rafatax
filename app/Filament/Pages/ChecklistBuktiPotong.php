<?php

namespace App\Filament\Pages;

use App\Models\CashReport;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\CheckboxColumn;
use Illuminate\Database\Eloquent\Builder;

class ChecklistBuktiPotong extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static string $view = 'filament.pages.checklist-bukti-potong';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Checklist Bukti Potong';

    protected static ?string $title = 'Checklist Bukti Potong';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CashReport::query()
                    ->whereIn('coa_id', [180, 188, 182, 183, 184, 185, 186, 187])
                    ->where('invoice_id', '!=', '0')
                    ->whereHas('invoice', function (Builder $q) {
                        $q->where(function ($q2) {
                            $q2->whereHas('client', function ($q3) {
                                $q3->where('type', 'pt');
                            })->orWhereHas('mou.client', function ($q3) {
                                $q3->where('type', 'pt');
                            });
                        });
                    })
            )
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal Transaksi')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('coa.code')
                    ->label('Kode CoA')
                    ->sortable(),
                TextColumn::make('coa.name')
                    ->label('Nama CoA')
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->label('No Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_name')
                    ->label('Nama Klien (PT)')
                    ->getStateUsing(fn($record) => $record->invoice?->client_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('invoice.client', function ($q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%");
                        })->orWhereHas('invoice.mou.client', function ($q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('debit_amount')
                    ->label('Debit')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('credit_amount')
                    ->label('Kredit')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_pph23_checked')
                    ->label('Checklist PPh23')
                    ->boolean()
                    ->action(
                        Tables\Actions\Action::make('toggleChecklist')
                            ->form(fn($record) => $record->is_pph23_checked ? [] : [
                                \Filament\Forms\Components\DatePicker::make('tanggal_bukti_potong_pph23')
                                    ->label('Tanggal Bukti Potong')
                                    ->required(),
                            ])
                            ->modalHeading(fn($record) => $record->is_pph23_checked ? 'Batalkan Checklist?' : 'Konfirmasi Checklist PPh23')
                            ->action(function ($record, array $data) {
                                if ($record->is_pph23_checked) {
                                    $record->update([
                                        'is_pph23_checked' => false,
                                        'tanggal_bukti_potong_pph23' => null,
                                    ]);
                                } else {
                                    $record->update([
                                        'is_pph23_checked' => true,
                                        'tanggal_bukti_potong_pph23' => $data['tanggal_bukti_potong_pph23'],
                                    ]);
                                }
                            })
                    )
                    ->sortable(),
                TextColumn::make('tanggal_bukti_potong_pph23')
                    ->label('Tgl Bukti Potong')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('belum_checklist')
                    ->label('Belum Checklist')
                    ->query(fn(Builder $query) => $query->where('is_pph23_checked', false))
                    ->toggle()
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
