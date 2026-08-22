<?php

namespace App\Filament\Pages;

use App\Models\Client;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\InvoiceResource;

class PiutangPerClient extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.pages.piutang-per-client';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Piutang per Client';

    protected static ?string $title = 'Piutang per Client';

    public function table(Table $table): Table
    {
        // 1. Saldo Awal Aggregated Subquery (>= 2025)
        $saSql = "
            SELECT client_id, SUM(amount) as saldo_awal
            FROM saldo_awal_piutangs
            WHERE year >= 2025
            GROUP BY client_id
        ";

        // 2. Invoices Aggregated Subquery (>= 2025)
        $invSql = "
            SELECT client_id, SUM(amount) as total_invoice
            FROM (
                SELECT 
                    COALESCE(NULLIF(i.client_id, 0), m.client_id) as client_id,
                    cli.amount
                FROM cost_list_invoices cli
                JOIN invoices i ON cli.invoice_id = i.id
                LEFT JOIN mous m ON (i.mou_id IS NOT NULL AND i.mou_id <> 0 AND i.mou_id = m.id)
                WHERE cli.deleted_at IS NULL 
                  AND i.deleted_at IS NULL
                  AND i.invoice_date >= '2025-01-01'
            ) as t_inv
            WHERE client_id IS NOT NULL
            GROUP BY client_id
        ";

        // 3. Payments Aggregated Subquery (>= 2025 dan bukan CoA 180)
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
                  AND cr.transaction_date >= '2025-01-01'
                  AND (cr.coa_id IS NULL OR cr.coa_id <> 180)
            ) as t_pay
            WHERE client_id IS NOT NULL
            GROUP BY client_id
        ";

        // 4. Potongan MoU Aggregated Subquery (>= 2025)
        $potSql = "
            SELECT 
                client_id,
                SUM(COALESCE(discount_amount, 0) + COALESCE(cancel_mou_amount, 0)) as total_potongan
            FROM mous
            WHERE deleted_at IS NULL
              AND (start_date >= '2025-01-01' OR (start_date IS NULL AND created_at >= '2025-01-01'))
            GROUP BY client_id
        ";

        return $table
            ->query(
                Client::query()
                    ->select('clients.*')
                    ->selectRaw('COALESCE(sa.saldo_awal, 0) as saldo_awal')
                    ->selectRaw('COALESCE(inv.total_invoice, 0) as total_invoice')
                    ->selectRaw('COALESCE(pay.total_pembayaran, 0) as total_pembayaran')
                    ->selectRaw('COALESCE(pot.total_potongan, 0) as total_potongan')
                    ->selectRaw('(COALESCE(sa.saldo_awal, 0) + COALESCE(inv.total_invoice, 0) - COALESCE(pay.total_pembayaran, 0) - COALESCE(pot.total_potongan, 0)) as total_piutang')
                    ->leftJoin(DB::raw("({$saSql}) as sa"), 'clients.id', '=', 'sa.client_id')
                    ->leftJoin(DB::raw("({$invSql}) as inv"), 'clients.id', '=', 'inv.client_id')
                    ->leftJoin(DB::raw("({$paySql}) as pay"), 'clients.id', '=', 'pay.client_id')
                    ->leftJoin(DB::raw("({$potSql}) as pot"), 'clients.id', '=', 'pot.client_id')
            )
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
                    ->label('Saldo Awal Piutang')
                    ->formatStateUsing(fn($state): string => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_invoice')
                    ->label('Total Invoice')
                    ->formatStateUsing(fn($state): string => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_pembayaran')
                    ->label('Total Pembayaran')
                    ->formatStateUsing(fn($state): string => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_piutang')
                    ->label('Sisa Piutang')
                    ->formatStateUsing(fn($state): string => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->weight('bold')
                    ->color(fn($state) => $state > 0 ? 'amber' : 'success'),
            ])
            ->filters([
                Tables\Filters\Filter::make('piutang_aktif')
                    ->label('Hanya Piutang Aktif')
                    ->query(fn(Builder $query) => $query->having('total_piutang', '>', 0))
                    ->default(false),
            ])
            ->actions([
                Tables\Actions\Action::make('view_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn($record) => route('piutang-per-client.detail', [
                        'id' => $record->id,
                        'periode' => 'post_2025',
                    ]))
                    ->openUrlInNewTab(),
            ]);
    }

    public function getClientTransactions(Client $client, ?string $periode = null): array
    {
        $periode = $periode ?? 'post_2025';
        $transactions = [];

        // 1. Saldo Awal
        $saldoAwalsQuery = DB::table('saldo_awal_piutangs')
            ->where('client_id', $client->id);

        if ($periode === 'pre_2025') {
            $saldoAwalsQuery->where('year', '<', 2025);
        } elseif ($periode === 'post_2025') {
            $saldoAwalsQuery->where('year', '>=', 2025);
        }

        $saldoAwals = $saldoAwalsQuery->orderBy('year', 'asc')->get();

        foreach ($saldoAwals as $sa) {
            if ($sa->amount > 0) {
                $periodeLabel = $sa->year < 2025 ? 'Sebelum 2025' : 'Tahun ' . $sa->year;
                $transactions[] = [
                    'date' => null,
                    'date_sort' => $sa->year < 2025 ? '0000-00-00' : "{$sa->year}-01-01",
                    'type' => 'Saldo Awal',
                    'ref' => $sa->year < 2025 ? '< 2025' : (string) $sa->year,
                    'description' => $sa->notes ?: "Saldo Awal Piutang ({$periodeLabel})",
                    'debit' => $sa->amount,
                    'kredit' => 0,
                    'amount' => $sa->amount,
                ];
            }
        }

        // 2. Invoices
        $invoicesQuery = \App\Models\Invoice::query()
            ->where(function ($q) use ($client) {
                $q->where('client_id', $client->id)
                    ->orWhereIn('mou_id', function ($sub) use ($client) {
                        $sub->select('id')->from('mous')->where('client_id', $client->id);
                    });
            })
            ->whereNull('deleted_at');

        if ($periode === 'pre_2025') {
            $invoicesQuery->where('invoice_date', '<', '2025-01-01');
        } elseif ($periode === 'post_2025') {
            $invoicesQuery->where('invoice_date', '>=', '2025-01-01');
        }

        $invoices = $invoicesQuery->with('costListInvoices')->get();

        foreach ($invoices as $inv) {
            $amount = $inv->costListInvoices->sum('amount');
            $transactions[] = [
                'date' => $inv->invoice_date,
                'date_sort' => $inv->invoice_date,
                'type' => 'Sales Invoice',
                'ref' => $inv->invoice_number,
                'description' => $inv->description ?: 'Tagihan Invoice',
                'debit' => $amount,
                'kredit' => 0,
                'amount' => $amount,
            ];
        }

        // 3. Payments (CashReport)
        $cashReportsQuery = \App\Models\CashReport::query()
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
            ->whereNull('deleted_at');

        if ($periode === 'pre_2025') {
            // Sebelum 2025: Pembayaran kas bank sebelum 2025 (non-180) ATAU transaksi kas bank CoA 180 (AO-103.5 Piutang Lama)
            $cashReportsQuery->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('transaction_date', '<', '2025-01-01')
                        ->where(function ($sub2) {
                            $sub2->whereNull('coa_id')->orWhere('coa_id', '<>', 180);
                        });
                })->orWhere('coa_id', 180);
            });
        } elseif ($periode === 'post_2025') {
            // Tahun 2025 ke Atas: Pembayaran >= 2025 dan bukan CoA 180
            $cashReportsQuery->where('transaction_date', '>=', '2025-01-01')
                ->where(function ($q) {
                    $q->whereNull('coa_id')->orWhere('coa_id', '<>', 180);
                });
        }

        $cashReports = $cashReportsQuery->with(['cashReference', 'invoice', 'coa'])->get();

        foreach ($cashReports as $cr) {
            $amount = $cr->debit_amount - $cr->credit_amount;
            $typeLabel = $cr->coa_id == 180 ? 'Sales Receipt (AO-103.5 - Piutang Lama)' : 'Sales Receipt';
            $transactions[] = [
                'date' => $cr->transaction_date,
                'date_sort' => $cr->transaction_date,
                'type' => $typeLabel,
                'ref' => $cr->invoice?->invoice_number ?: ($cr->cashReference?->name ?: '-'),
                'description' => $cr->description,
                'debit' => 0,
                'kredit' => $amount,
                'amount' => -$amount,
            ];
        }

        // 4. Discounts and Cancel MoUs from MoU model
        $mousQuery = \App\Models\MoU::query()
            ->where('client_id', $client->id)
            ->whereNull('deleted_at');

        if ($periode === 'pre_2025') {
            $mousQuery->where(function ($q) {
                $q->where('start_date', '<', '2025-01-01')
                    ->orWhere(function ($sub) {
                        $sub->whereNull('start_date')->where('created_at', '<', '2025-01-01');
                    });
            });
        } elseif ($periode === 'post_2025') {
            $mousQuery->where(function ($q) {
                $q->where('start_date', '>=', '2025-01-01')
                    ->orWhere(function ($sub) {
                        $sub->whereNull('start_date')->where('created_at', '>=', '2025-01-01');
                    });
            });
        }

        $mous = $mousQuery->get();

        foreach ($mous as $mou) {
            if ($mou->discount_amount > 0) {
                $tglDiscount = $mou->tgl_discount;
                $transactions[] = [
                    'date' => $tglDiscount,
                    'date_sort' => $tglDiscount ?: '9999-12-31',
                    'type' => 'Discount MoU',
                    'ref' => $mou->mou_number ?: 'MoU #' . $mou->id,
                    'description' => 'Discount MoU' . ($mou->description ? " - {$mou->description}" : ''),
                    'debit' => 0,
                    'kredit' => $mou->discount_amount,
                    'amount' => -$mou->discount_amount,
                ];
            }

            if ($mou->cancel_mou_amount > 0) {
                $tglCancel = $mou->tgl_cancel_mou;
                $transactions[] = [
                    'date' => $tglCancel,
                    'date_sort' => $tglCancel ?: '9999-12-31',
                    'type' => 'Cancel MoU',
                    'ref' => $mou->mou_number ?: 'MoU #' . $mou->id,
                    'description' => 'Cancel MoU' . ($mou->description ? " - {$mou->description}" : ''),
                    'debit' => 0,
                    'kredit' => $mou->cancel_mou_amount,
                    'amount' => -$mou->cancel_mou_amount,
                ];
            }
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
        // Get the base query with filters/search applied
        $query = $this->getFilteredTableQuery();

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $stats = DB::selectOne("
            SELECT 
                SUM(temp.saldo_awal) as total_saldo_awal,
                SUM(temp.total_invoice) as total_invoice,
                SUM(temp.total_pembayaran) as total_pembayaran,
                SUM(temp.total_piutang) as total_piutang
            FROM ({$sql}) as temp
        ", $bindings);

        return [
            'total_saldo_awal' => $stats->total_saldo_awal ?? 0,
            'total_invoice' => $stats->total_invoice ?? 0,
            'total_pembayaran' => $stats->total_pembayaran ?? 0,
            'total_piutang' => $stats->total_piutang ?? 0,
        ];
    }
}
