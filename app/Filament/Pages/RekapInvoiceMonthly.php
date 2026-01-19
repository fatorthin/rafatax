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

class RekapInvoiceMonthly extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.pages.rekap-invoice-monthly';

    protected static ?string $slug = 'rekap-invoice-monthly';

    // Hidden from navigation, accessed via RekapInvoice
    protected static bool $shouldRegisterNavigation = false;

    public $type;

    public function mount()
    {
        $this->type = request()->query('type');
        if (!$this->type) {
            // Fallback or 404
            $this->type = 'pt'; // Default or handle error
        }
    }

    public function getTitle(): string
    {
        return 'Rekap Invoice Bulanan - ' . strtoupper($this->type ?? '');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month')
                    ->where('invoice_type', $this->type)
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->addSelect(DB::raw('(
                        SELECT COUNT(*) 
                        FROM invoices as i 
                        WHERE YEAR(i.created_at) = YEAR(invoices.created_at) 
                        AND MONTH(i.created_at) = MONTH(invoices.created_at) 
                        AND i.invoice_type = invoices.invoice_type
                        AND i.deleted_at IS NULL
                    ) as invoice_count'))
                    ->addSelect(DB::raw('(
                        SELECT SUM(cli.amount) 
                        FROM cost_list_invoices as cli
                        JOIN invoices as i2 ON cli.invoice_id = i2.id
                        WHERE YEAR(i2.created_at) = YEAR(invoices.created_at) 
                        AND MONTH(i2.created_at) = MONTH(invoices.created_at)
                        AND i2.invoice_type = invoices.invoice_type 
                        AND i2.deleted_at IS NULL
                        AND cli.deleted_at IS NULL
                    ) as total_amount'))
            )
            ->columns([
                TextColumn::make('year')
                    ->label('Tahun'),
                TextColumn::make('month')
                    ->label('Bulan')
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::create()->month($state)->format('F')),
                TextColumn::make('invoice_count')
                    ->label('Jumlah Invoice'),
                TextColumn::make('total_amount')
                    ->label('Total Nilai')
                    ->numeric(locale: 'id')
                    ->alignEnd(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_list')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn($record): string => InvoiceResource::getUrl('index', [
                        'tableFilters' => [
                            'invoice_type' => [
                                'value' => $this->type,
                            ],
                            'date_range' => [
                                'month' => $record->month,
                                'year' => $record->year,
                            ],
                        ],
                    ])),
            ])
            ->paginated(false);
    }

    public function getTableRecordKey($record): string
    {
        return $record->year . '-' . $record->month;
    }
}
