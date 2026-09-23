<?php

namespace App\Filament\Pages;

use App\Models\Client;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Helpers\PiutangLamaPerClientExporter;

class PiutangLamaPerClient extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static string $view = 'filament.pages.piutang-lama-per-client';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Piutang Lama Per Klien';

    protected static ?string $title = 'Piutang Lama Per Klien';

    /**
     * Base query for Piutang Lama per Client with all aggregated subqueries
     */
    public static function getBasePiutangQuery(): Builder
    {
        // 1. Saldo Awal Piutang Lama (Periode 2024)
        $saSql = "
            SELECT client_id, SUM(amount) as saldo_awal
            FROM saldo_awal_piutangs
            WHERE year = 2024
            GROUP BY client_id
        ";

        // 2. Pembayaran Piutang Lama (Transaksi sebelum 2026 ATAU CoA 180 AO-103.5)
        $paySql = "
            SELECT client_id, SUM(amount) as total_pembayaran
            FROM (
                SELECT 
                    COALESCE(
                        NULLIF(cr.client_id, 0),
                        m.client_id,
                        inv.client_id,
                        inv_m.client_id
                    ) as client_id,
                    (cr.debit_amount - cr.credit_amount) as amount
                FROM cash_reports cr
                LEFT JOIN mous m ON (cr.mou_id IS NOT NULL AND cr.mou_id <> '0' AND cr.mou_id = m.id)
                LEFT JOIN invoices inv ON (cr.invoice_id IS NOT NULL AND cr.invoice_id <> 0 AND cr.invoice_id = inv.id)
                LEFT JOIN mous inv_m ON (inv.mou_id IS NOT NULL AND inv.mou_id <> 0 AND inv.mou_id = inv_m.id)
                WHERE cr.deleted_at IS NULL
                  AND (
                      (cr.transaction_date < '2026-01-01' AND (cr.coa_id IS NULL OR cr.coa_id <> 180))
                      OR cr.coa_id = 180
                  )
            ) as t_pay
            WHERE client_id IS NOT NULL
            GROUP BY client_id
        ";

        return Client::query()
            ->select('clients.*')
            ->selectRaw('COALESCE(sa.saldo_awal, 0) as saldo_awal')
            ->selectRaw('COALESCE(pay.total_pembayaran, 0) as total_pembayaran')
            ->selectRaw('(COALESCE(sa.saldo_awal, 0) - COALESCE(pay.total_pembayaran, 0)) as total_piutang')
            ->leftJoin(DB::raw("({$saSql}) as sa"), 'clients.id', '=', 'sa.client_id')
            ->leftJoin(DB::raw("({$paySql}) as pay"), 'clients.id', '=', 'pay.client_id');
    }

    /**
     * Form schema for Export Excel modal
     */
    public static function getExportFormSchema(): array
    {
        return [
            Radio::make('export_scope')
                ->label('Cakupan Data Export')
                ->options([
                    'current' => 'Sesuai Tampilan / Filter Tabel Saat Ini',
                    'custom' => 'Kustom (Pilih Filter di Bawah)',
                ])
                ->default('current')
                ->reactive(),
            Select::make('client_type')
                ->label('Jenis Klien')
                ->options([
                    'all' => 'Semua Jenis (PT & KKP)',
                    'pt' => 'Hanya PT',
                    'kkp' => 'Hanya KKP',
                ])
                ->default('all')
                ->visible(fn(Get $get) => $get('export_scope') === 'custom'),
            Select::make('piutang_status')
                ->label('Status Piutang')
                ->options([
                    'all' => 'Semua (Termasuk Lunas)',
                    'aktif' => 'Hanya Piutang Aktif (Sisa > 0)',
                    'lunas' => 'Hanya Klien Lunas (Sisa <= 0)',
                ])
                ->default('all')
                ->visible(fn(Get $get) => $get('export_scope') === 'custom'),
        ];
    }

    public function handleExport(array $data)
    {
        $scope = $data['export_scope'] ?? 'current';
        $subtitleParts = [];

        if ($scope === 'custom') {
            $query = static::getBasePiutangQuery();

            if (!empty($data['client_type']) && $data['client_type'] !== 'all') {
                $query->where('clients.type', $data['client_type']);
                $subtitleParts[] = 'Jenis: ' . strtoupper($data['client_type']);
            }

            if (($data['piutang_status'] ?? 'all') === 'aktif') {
                $query->having('total_piutang', '>', 0);
                $subtitleParts[] = 'Status: Piutang Aktif';
            } elseif (($data['piutang_status'] ?? 'all') === 'lunas') {
                $query->having('total_piutang', '<=', 0);
                $subtitleParts[] = 'Status: Lunas';
            }

            if (empty($subtitleParts)) {
                $subtitleParts[] = 'Semua Klien';
            }
        } else {
            $query = $this->getFilteredTableQuery();
            $subtitleParts[] = 'Sesuai Filter Tabel';
        }

        // Apply table sorting if available and not yet ordered
        $sortColumn = $this->getTableSortColumn();
        $sortDirection = $this->getTableSortDirection() ?? 'asc';

        if (!empty($sortColumn)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('clients.company_name', 'asc');
        }

        $records = $query->get();
        $subtitle = implode(' | ', $subtitleParts);

        return PiutangLamaPerClientExporter::export($records, $subtitle);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::getBasePiutangQuery())
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->label('Nama Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('saldo_awal')
                    ->label('Saldo Awal (2024)')
                    ->formatStateUsing(fn($state): string => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_pembayaran')
                    ->label('Total Pelunasan / CoA 180')
                    ->formatStateUsing(fn($state): string => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_piutang')
                    ->label('Sisa Piutang Lama')
                    ->formatStateUsing(fn($state): string => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->weight('bold')
                    ->color(fn($state) => $state > 0 ? 'amber' : 'success'),
            ])
            ->filters([
                Tables\Filters\Filter::make('piutang_aktif')
                    ->label('Hanya Piutang Aktif (> 0)')
                    ->query(fn(Builder $query) => $query->having('total_piutang', '>', 0))
                    ->default(false),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->modalHeading('Export Data Piutang Lama Per Klien')
                    ->modalDescription('Unduh data rekap piutang lama per client ke format file Excel (.xlsx).')
                    ->modalSubmitActionLabel('Unduh Excel')
                    ->form(static::getExportFormSchema())
                    ->action(fn(array $data) => $this->handleExport($data)),
            ])
            ->actions([
                Tables\Actions\Action::make('view_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn($record) => route('piutang-lama-per-client.detail', [
                        'id' => $record->id,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_selected')
                    ->label('Export Excel (Klien Terpilih)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        return PiutangLamaPerClientExporter::export($records, 'Klien Terpilih');
                    }),
            ]);
    }

    public function getClientTransactions(Client $client): array
    {
        $transactions = [];

        // 1. Saldo Awal Piutang Lama (Periode 2025)
        $saldoAwals = DB::table('saldo_awal_piutangs')
            ->where('client_id', $client->id)
            ->where('year', 2025)
            ->orderBy('year', 'asc')
            ->get();

        foreach ($saldoAwals as $sa) {
            if ((float) $sa->amount != 0.0) {
                $isDebit = (float) $sa->amount > 0;
                $transactions[] = [
                    'date' => null,
                    'date_sort' => "2025-01-01",
                    'type' => 'Saldo Awal',
                    'ref' => (string) $sa->year,
                    'description' => $sa->notes ?: "Saldo Awal Piutang (Tahun {$sa->year})",
                    'debit' => $isDebit ? (float) $sa->amount : 0,
                    'kredit' => !$isDebit ? abs((float) $sa->amount) : 0,
                    'amount' => (float) $sa->amount,
                ];
            }
        }

        // 2. Payments (< 2026 ATAU CoA 180 AO-103.5)
        $cashReports = \App\Models\CashReport::query()
            ->where(function ($q) use ($client) {
                $q->where('cash_reports.client_id', $client->id)
                    ->orWhereIn('cash_reports.mou_id', function ($sub) use ($client) {
                        $sub->select('id')->from('mous')->where('client_id', $client->id);
                    })
                    ->orWhereIn('cash_reports.invoice_id', function ($sub) use ($client) {
                        $sub->select('id')->from('invoices')
                            ->where('client_id', $client->id)
                            ->orWhereIn('mou_id', function ($sub2) use ($client) {
                                $sub2->select('id')->from('mous')->where('client_id', $client->id);
                            });
                    });
            })
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('transaction_date', '<', '2026-01-01')
                        ->where(function ($sub2) {
                            $sub2->whereNull('coa_id')->orWhere('coa_id', '<>', 180);
                        });
                })->orWhere('coa_id', 180);
            })
            ->with(['cashReference', 'invoice', 'coa'])
            ->get();

        foreach ($cashReports as $cr) {
            $amount = $cr->debit_amount - $cr->credit_amount;
            $typeLabel = $cr->coa_id == 180 ? 'Sales Receipt (AO-103.5 - Piutang Lama)' : 'Sales Receipt';
            $transactions[] = [
                'date' => $cr->transaction_date,
                'date_sort' => $cr->transaction_date,
                'type' => $typeLabel,
                'ref' => $cr->invoice?->invoice_number ?: ($cr->cashReference?->name ?: '-'),
                'cash_reference_name' => $cr->cashReference?->name,
                'cash_reference_id' => $cr->cash_reference_id,
                'description' => $cr->description,
                'debit' => 0,
                'kredit' => $amount,
                'amount' => -$amount,
            ];
        }

        // Sort transactions chronologically
        usort($transactions, function ($a, $b) {
            if ($a['date_sort'] === $b['date_sort']) {
                if ($a['type'] === 'Saldo Awal') return -1;
                if ($b['type'] === 'Saldo Awal') return 1;
                return $a['type'] <=> $b['type'];
            }
            return $a['date_sort'] <=> $b['date_sort'];
        });

        // Calculate running balance
        $runningBalance = 0;
        foreach ($transactions as &$tx) {
            $runningBalance += $tx['amount'];
            $tx['running_balance'] = $runningBalance;
        }

        return $transactions;
    }

    public function getStats(): array
    {
        $query = $this->getFilteredTableQuery();

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $stats = DB::selectOne("
            SELECT 
                SUM(temp.saldo_awal) as total_saldo_awal,
                SUM(temp.total_pembayaran) as total_pembayaran,
                SUM(temp.total_piutang) as total_piutang
            FROM ({$sql}) as temp
        ", $bindings);

        return [
            'total_saldo_awal' => $stats->total_saldo_awal ?? 0,
            'total_pembayaran' => $stats->total_pembayaran ?? 0,
            'total_piutang' => $stats->total_piutang ?? 0,
        ];
    }
}
