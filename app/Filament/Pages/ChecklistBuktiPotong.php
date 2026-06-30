<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
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
                Invoice::query()
                    ->where(function (Builder $query) {
                        $query->whereHas('client', function (Builder $q) {
                            $q->where('type', 'pt');
                        })->orWhereHas('mou.client', function (Builder $q) {
                            $q->where('type', 'pt');
                        });
                    })
            )
            ->columns([
                TextColumn::make('invoice_date')
                    ->label('Tanggal Invoice')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->label('No Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_name')
                    ->label('Nama Klien (PT)')
                    ->getStateUsing(fn($record) => $record->client_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('client', function ($q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%");
                        })->orWhereHas('mou.client', function ($q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Nilai Invoice')
                    ->money('IDR', locale: 'id')
                    ->getStateUsing(fn($record) => $record->total_amount),
                TextColumn::make('nominal_pph23')
                    ->label('Nominal PPh23')
                    ->money('IDR', locale: 'id')
                    ->getStateUsing(fn($record) => $record->total_amount / 98 * 2),
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
            ->actions([
                Tables\Actions\Action::make('viewDetails')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalContent(fn($record) => view('filament.components.invoice-details', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalHeading('Detail Invoice'),
            ])
            ->bulkActions([]);
    }
}
