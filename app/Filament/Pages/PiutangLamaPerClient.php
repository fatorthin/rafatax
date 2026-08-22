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

class PiutangLamaPerClient extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static string $view = 'filament.pages.piutang-lama-per-client';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Piutang Lama Per Klien';

    protected static ?string $title = 'Piutang Lama Per Klien';

    public function table(Table $table): Table
    {
        // 1. Saldo Awal Piutang Lama (< 2025)
        $saSql = "
            SELECT client_id, SUM(amount) as saldo_awal
            FROM saldo_awal_piutangs
            WHERE year < 2025
            GROUP BY client_id
        ";

        // 2. Invoices Sebelum 2025
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
                  AND i.invoice_date < '2025-01-01'
            ) as t_inv
            WHERE client_id IS NOT NULL
            GROUP BY client_id
        ";

        // 3. Pembayaran Piutang Lama (Transaksi sebelum 2025 ATAU CoA 180 AO-103.5)
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
                      (cr.transaction_date < '2025-01-01' AND (cr.coa_id IS NULL OR cr.coa_id <> 180))
                      OR cr.coa_id = 180
                  )
            ) as t_pay
            WHERE client_id IS NOT NULL
            GROUP BY client_id
        ";

        // 4. Potongan MoU Sebelum 2025
        $potSql = "
            SELECT 
                client_id,
                SUM(COALESCE(discount_amount, 0) + COALESCE(cancel_mou_amount, 0)) as total_potongan
            FROM mous
            WHERE deleted_at IS NULL
              AND (start_date < '2025-01-01' OR (start_date IS NULL AND created_at < '2025-01-01'))
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
                    ->label('Saldo Awal Piutang Lama')
                    ->formatStateUsing(fn($state): string => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_invoice')
                    ->label('Total Invoice (< 2025)')
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
            ->actions([
                Tables\Actions\Action::make('view_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn($record) => route('piutang-per-client.detail', [
                        'id' => $record->id,
                        'periode' => 'pre_2025',
                    ]))
                    ->openUrlInNewTab(),
            ]);
    }

    public function getStats(): array
    {
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
