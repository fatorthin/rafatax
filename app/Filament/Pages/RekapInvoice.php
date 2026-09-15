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
            ->leftJoin('mous as m', 'invoices.mou_id', '=', 'm.id')
            ->leftJoin('clients as c', function ($join) {
                $join->on(DB::raw('COALESCE(NULLIF(invoices.client_id, 0), m.client_id)'), '=', 'c.id');
            })
            ->whereNotNull('invoices.invoice_date')
            ->selectRaw('YEAR(invoices.invoice_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [(int) date('Y')];
        }

        $clientTypeExpression = 'LOWER(COALESCE(c.type, m.type, invoices.invoice_type))';

        $selects = [
            DB::raw("{$clientTypeExpression} as client_type"),
            DB::raw('COUNT(DISTINCT invoices.id) as invoice_count'),
            DB::raw('COALESCE(SUM(cli.amount), 0) as total_amount'),
        ];

        foreach ($years as $year) {
            $selects[] = DB::raw("COALESCE(SUM(CASE WHEN YEAR(invoices.invoice_date) = {$year} THEN cli.amount ELSE 0 END), 0) as total_{$year}");
        }

        $columns = [
            TextColumn::make('client_type')
                ->label('Tipe Client')
                ->formatStateUsing(fn($state) => strtoupper((string) $state))
                ->sortable()
                ->searchable(),
            TextColumn::make('invoice_count')
                ->label('Jumlah Invoice')
                ->sortable(),
        ];

        foreach ($years as $year) {
            $columns[] = TextColumn::make("total_{$year}")
                ->label("Nominal {$year}")
                ->formatStateUsing(fn($state): string => 'Rp ' . number_format((float) ($state ?? 0), 0, ',', '.'))
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
                return 'Rp ' . number_format($sum, 0, ',', '.');
            });

        return $table
            ->query(
                Invoice::query()
                    ->leftJoin('mous as m', 'invoices.mou_id', '=', 'm.id')
                    ->leftJoin('clients as c', function ($join) {
                        $join->on(DB::raw('COALESCE(NULLIF(invoices.client_id, 0), m.client_id)'), '=', 'c.id');
                    })
                    ->leftJoin('cost_list_invoices as cli', 'cli.invoice_id', '=', 'invoices.id')
                    ->select($selects)
                    ->whereNotNull('invoices.invoice_date')
                    ->whereNull('invoices.memo_id')
                    ->whereNull('invoices.deleted_at')
                    ->whereNull('cli.deleted_at')
                    ->whereIn(DB::raw($clientTypeExpression), ['pt', 'kkp'])
                    ->groupByRaw($clientTypeExpression)
                    ->orderByRaw("{$clientTypeExpression} ASC")
            )
            ->columns($columns)
            ->actions([
                Tables\Actions\Action::make('view_list')
                    ->label('Lihat List')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn($record): string => InvoiceResource::getUrl('index', [
                        'tableFilters' => [
                            'invoice_type' => [
                                'value' => $record->client_type,
                            ],
                        ],
                    ])),
                Tables\Actions\Action::make('view_monthly')
                    ->label('Lihat Bulanan')
                    ->icon('heroicon-o-calendar')
                    ->action(function ($record) {
                        return redirect()->to(RekapInvoiceMonthly::getUrl(['type' => $record->client_type]));
                    }),
            ])
            ->paginated(false);
    }

    // Must be overridden to support the distinct string key
    public function getTableRecordKey($record): string
    {
        return $record->client_type;
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
