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
        return $table
            ->query(
                Client::query()
                    ->select('clients.*')
                    ->selectRaw('COALESCE((SELECT SUM(amount) FROM saldo_awal_piutangs WHERE client_id = clients.id), 0) as saldo_awal')
                    ->selectRaw('COALESCE((
                        SELECT SUM(cli.amount)
                        FROM cost_list_invoices cli
                        JOIN invoices i ON cli.invoice_id = i.id
                        WHERE (i.client_id = clients.id OR (i.mou_id IS NOT NULL AND i.mou_id <> 0 AND i.mou_id IN (SELECT id FROM mous WHERE client_id = clients.id)))
                        AND i.deleted_at IS NULL
                        AND cli.deleted_at IS NULL
                    ), 0) as total_invoice')
                    ->selectRaw('COALESCE((
                        SELECT SUM(cr.debit_amount - cr.credit_amount)
                        FROM cash_reports cr
                        WHERE (
                            (cr.client_id IS NOT NULL AND cr.client_id <> 0 AND cr.client_id = clients.id)
                            OR
                            (cr.mou_id IS NOT NULL AND cr.mou_id <> \'0\' AND cr.mou_id IN (SELECT id FROM mous WHERE client_id = clients.id))
                        )
                        AND cr.deleted_at IS NULL
                    ), 0) as total_pembayaran')
                    ->selectRaw('(
                        COALESCE((SELECT SUM(amount) FROM saldo_awal_piutangs WHERE client_id = clients.id), 0)
                        +
                        COALESCE((
                            SELECT SUM(cli.amount)
                            FROM cost_list_invoices cli
                            JOIN invoices i ON cli.invoice_id = i.id
                            WHERE (i.client_id = clients.id OR (i.mou_id IS NOT NULL AND i.mou_id <> 0 AND i.mou_id IN (SELECT id FROM mous WHERE client_id = clients.id)))
                            AND i.deleted_at IS NULL
                            AND cli.deleted_at IS NULL
                        ), 0)
                        -
                        COALESCE((
                            SELECT SUM(cr.debit_amount - cr.credit_amount)
                            FROM cash_reports cr
                            WHERE (
                                (cr.client_id IS NOT NULL AND cr.client_id <> 0 AND cr.client_id = clients.id)
                                OR
                                (cr.mou_id IS NOT NULL AND cr.mou_id <> \'0\' AND cr.mou_id IN (SELECT id FROM mous WHERE client_id = clients.id))
                            )
                            AND cr.deleted_at IS NULL
                        ), 0)
                    ) as total_piutang')
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
                    ->modalContent(fn($record) => view('filament.pages.piutang-detail-modal', [
                        'client' => $record,
                        'transactions' => $this->getClientTransactions($record),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->slideOver()
                    ->modalWidth('5xl'),
            ]);
    }

    public function getClientTransactions(Client $client): array
    {
        $transactions = [];

        // 1. Saldo Awal
        $saldoAwal = DB::table('saldo_awal_piutangs')
            ->where('client_id', $client->id)
            ->sum('amount');

        if ($saldoAwal > 0) {
            $transactions[] = [
                'date' => null,
                'date_sort' => '0000-00-00',
                'type' => 'Saldo Awal',
                'ref' => '-',
                'description' => 'Saldo Awal Piutang',
                'debit' => $saldoAwal,
                'kredit' => 0,
                'amount' => $saldoAwal,
            ];
        }

        // 2. Invoices (Only from 2026-01-01 onwards)
        $invoices = \App\Models\Invoice::query()
            ->where(function($q) use ($client) {
                $q->where('client_id', $client->id)
                  ->orWhereIn('mou_id', function($sub) use ($client) {
                      $sub->select('id')->from('mous')->where('client_id', $client->id);
                  });
            })
            ->where('invoice_date', '>=', '2026-01-01')
            ->with('costListInvoices')
            ->get();

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

        // 3. Payments (CashReport - Only from 2026-01-01 onwards)
        $cashReports = \App\Models\CashReport::query()
            ->where(function($q) use ($client) {
                $q->where('client_id', $client->id)
                  ->orWhereIn('mou_id', function($sub) use ($client) {
                      $sub->select('id')->from('mous')->where('client_id', $client->id);
                  });
            })
            ->whereNull('deleted_at')
            ->where('transaction_date', '>=', '2026-01-01')
            ->with(['cashReference', 'invoice'])
            ->get();

        foreach ($cashReports as $cr) {
            $amount = $cr->debit_amount - $cr->credit_amount;
            $transactions[] = [
                'date' => $cr->transaction_date,
                'date_sort' => $cr->transaction_date,
                'type' => 'Sales Receipt',
                'ref' => $cr->invoice?->invoice_number ?: ($cr->cashReference?->name ?: '-'),
                'description' => $cr->description,
                'debit' => 0,
                'kredit' => $amount,
                'amount' => -$amount,
            ];
        }

        // Sort transactions chronologically
        usort($transactions, function($a, $b) {
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
