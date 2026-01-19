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

class RekapInvoice extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.rekap-invoice';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Rekap Invoice';

    protected static ?string $title = 'Rekap Invoice';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->select('invoice_type')
                    ->whereNotNull('invoice_type')
                    ->where('invoice_type', '!=', '')
                    ->distinct()
                    ->addSelect(DB::raw('(SELECT COUNT(*) FROM invoices as i WHERE i.invoice_type = invoices.invoice_type AND i.deleted_at IS NULL) as invoice_count'))
                    ->addSelect(DB::raw('(
                        SELECT SUM(cli.amount) 
                        FROM cost_list_invoices as cli
                        JOIN invoices as i2 ON cli.invoice_id = i2.id
                        WHERE i2.invoice_type = invoices.invoice_type 
                        AND i2.deleted_at IS NULL
                        AND cli.deleted_at IS NULL
                    ) as total_amount'))
            )
            ->columns([
                TextColumn::make('invoice_type')
                    ->label('Tipe Invoice')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('invoice_count')
                    ->label('Jumlah Invoice')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total Nilai')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_list')
                    ->label('Lihat List')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn($record): string => InvoiceResource::getUrl('index', [
                        'tableFilters' => [
                            'invoice_type' => [ // Assuming filter exists or needs to be generic
                                'value' => $record->invoice_type,
                            ],
                        ],
                    ])),
                Tables\Actions\Action::make('view_monthly')
                    ->label('Lihat Bulanan')
                    ->icon('heroicon-o-calendar')
                    ->action(function ($record) {
                        return redirect()->to(RekapInvoiceMonthly::getUrl(['type' => $record->invoice_type]));
                    }),
            ])
            ->paginated(false);
    }

    // Must be overridden to support the distinct string key
    public function getTableRecordKey($record): string
    {
        return $record->invoice_type;
    }

    public function getTableRecord(?string $key): ?\Illuminate\Database\Eloquent\Model
    {
        if (!$key) return null;

        return Invoice::query()
            ->where('invoice_type', $key)
            ->first();
    }
}
