<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\InvoiceResource;

class RekapInvoiceTahunan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.rekap-invoice-tahunan';

    protected static ?string $slug = 'rekap-invoice-tahunan';

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string
    {
        return 'Rekap Invoice Tahunan';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->selectRaw('YEAR(created_at) as year')
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->addSelect(DB::raw('(
                        SELECT COUNT(*) 
                        FROM invoices as i 
                        WHERE YEAR(i.created_at) = YEAR(invoices.created_at) 
                        AND i.deleted_at IS NULL
                    ) as invoice_count'))
                    ->addSelect(DB::raw('(
                        SELECT COUNT(*) 
                        FROM invoices as i 
                        WHERE YEAR(i.created_at) = YEAR(invoices.created_at) 
                        AND i.invoice_type = \'pt\'
                        AND i.deleted_at IS NULL
                    ) as invoice_pt_count'))
                    ->addSelect(DB::raw('(
                        SELECT COUNT(*) 
                        FROM invoices as i 
                        WHERE YEAR(i.created_at) = YEAR(invoices.created_at) 
                        AND i.invoice_type = \'kkp\'
                        AND i.deleted_at IS NULL
                    ) as invoice_kkp_count'))
                    ->addSelect(DB::raw('(
                        SELECT SUM(cli.amount) 
                        FROM cost_list_invoices as cli
                        JOIN invoices as i2 ON cli.invoice_id = i2.id
                        WHERE YEAR(i2.created_at) = YEAR(invoices.created_at) 
                        AND i2.deleted_at IS NULL
                        AND cli.deleted_at IS NULL
                    ) as total_amount'))
            )
            ->columns([
                TextColumn::make('year')
                    ->label('Tahun'),
                TextColumn::make('invoice_pt_count')
                    ->label('Jumlah Invoice PT'),
                TextColumn::make('invoice_kkp_count')
                    ->label('Jumlah Invoice KKP'),
                TextColumn::make('invoice_count')
                    ->label('Total Jumlah Invoice'),
                TextColumn::make('total_amount')
                    ->label('Total Nilai')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->alignEnd(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_list')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn($record): string => InvoiceResource::getUrl('index', [
                        'tableFilters' => [
                            'date_range' => [
                                'year' => $record->year,
                            ],
                        ],
                    ])),
            ])
            ->paginated(false);
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->year;
    }
}
