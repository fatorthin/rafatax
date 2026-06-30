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
                    ->whereNotNull('invoice_id')
                    ->whereHas('invoice.client', function (Builder $query) {
                        $query->where('type', 'pt');
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
                TextColumn::make('invoice.client.company_name')
                    ->label('Nama Klien (PT)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('debit_amount')
                    ->label('Debit')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('credit_amount')
                    ->label('Kredit')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                CheckboxColumn::make('is_pph23_checked')
                    ->label('Checklist PPh23')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\ToggledFilter::make('is_pph23_checked')
                    ->label('Belum Checklist')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_pph23_checked', true),
                        false: fn (Builder $query) => $query->where('is_pph23_checked', false),
                    )
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
