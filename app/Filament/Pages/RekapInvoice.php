<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        $years = Invoice::query()
            ->whereNotNull('invoice_type')
            ->where('invoice_type', '!=', '')
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [(int) date('Y')];
        }

        $selects = [
            'invoices.invoice_type',
            DB::raw('COUNT(DISTINCT invoices.id) as invoice_count'),
            DB::raw('(
                SELECT COALESCE(SUM(cli.amount), 0)
                FROM cost_list_invoices cli
                JOIN invoices i2 ON cli.invoice_id = i2.id
                WHERE i2.invoice_type = invoices.invoice_type
                AND i2.deleted_at IS NULL
                AND cli.deleted_at IS NULL
            ) as total_amount'),
        ];

        foreach ($years as $year) {
            $selects[] = DB::raw("(
                SELECT COALESCE(SUM(cli.amount), 0)
                FROM cost_list_invoices cli
                JOIN invoices i2 ON cli.invoice_id = i2.id
                WHERE i2.invoice_type = invoices.invoice_type
                AND YEAR(i2.created_at) = {$year}
                AND i2.deleted_at IS NULL
                AND cli.deleted_at IS NULL
            ) as total_{$year}");
        }

        $columns = [
            TextColumn::make('invoice_type')
                ->label('Tipe Invoice')
                ->formatStateUsing(fn ($state) => strtoupper($state))
                ->sortable()
                ->searchable(),
            TextColumn::make('invoice_count')
                ->label('Jumlah Invoice')
                ->sortable(),
        ];

        foreach ($years as $year) {
            $columns[] = TextColumn::make("total_{$year}")
                ->label("Nominal {$year}")
                ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) ($state ?? 0), 0, ',', '.'))
                ->sortable();
        }

        $columns[] = TextColumn::make('total_overall')
            ->label('Total Nilai (Keseluruhan)')
            ->state(function ($record) use ($years): string {
                $sum = 0;
                foreach ($years as $year) {
                    $prop = "total_{$year}";
                    $sum += (float) ($record->$prop ?? 0);
                }
                return 'Rp '.number_format($sum, 0, ',', '.');
            });

        return $table
            ->query(
                Invoice::query()
                    ->select($selects)
                    ->whereNotNull('invoices.invoice_type')
                    ->where('invoices.invoice_type', '!=', '')
                    ->groupBy('invoices.invoice_type')
            )
            ->columns($columns)
            ->actions([
                Tables\Actions\Action::make('view_list')
                    ->label('Lihat List')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn ($record): string => InvoiceResource::getUrl('index', [
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rekap_kasus')
                ->label('Rekap Berdasarkan Kasus')
                ->url(RekapInvoiceKasus::getUrl())
                ->color('success')
                ->icon('heroicon-o-folder-open'),
            Action::make('rekap_tahunan')
                ->label('Rekap Tahunan')
                ->url(RekapInvoiceTahunan::getUrl())
                ->color('primary')
                ->icon('heroicon-o-calendar-days'),
        ];
    }
}
