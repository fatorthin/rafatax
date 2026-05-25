<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use App\Models\Coa;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Neraca Lajur Bulanan (Konsep Piutang)
 *
 * Kolom Jurnal Pendapatan pada halaman ini menggunakan sumber data baru:
 *  - Piutang (Debit AO-103 / Kredit Pendapatan): dari MoU approved dan Memo pada bulan berjalan
 *  - Realisasi Invoice (Debit Pendapatan / Kredit AO-103): dari Invoice yang terbit pada bulan berjalan
 *
 * Konsep jurnal:
 *   Saat MoU/Memo approved di bulan ini:
 *     Debit  : AO-103 (Piutang Usaha)         = total nilai MoU per COA + total Memo
 *     Kredit : COA Pendapatan (119-126 dsb.)   = nilai per COA
 *
 *   Saat Invoice diterbitkan (bulan ini):
 *     Debit  : COA Pendapatan (dari cost_list_invoices.coa_id)  = nilai invoice per COA
 *     Kredit : AO-103 (Piutang Usaha)                           = total invoice
 */
class NeracaLajurPiutang extends Page implements HasTable
{
    use InteractsWithTable;

    private const NERACA_GROUP_IDS = [10, 11, 12, 20, 21, 30];
    private const LABA_RUGI_GROUP_IDS = [40, 50, 60, 70];
    private const LABA_RUGI_PENDAPATAN_GROUP_IDS = [40, 60];

    // COA ID untuk Piutang Usaha (AO-103)
    private const COA_PIUTANG_USAHA_ID = 179;

    protected static string $resource = CashReportResource::class;
    protected static string $view = 'filament.resources.cash-report-resource.pages.neraca-lajur-piutang';

    protected static ?string $title = 'Neraca Lajur (Konsep Piutang)';

    public ?int $month = null;
    public ?int $year = null;

    public function mount(): void
    {
        $this->month = (int) request('month', now()->month);
        $this->year  = (int) request('year', now()->year);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter Periode')
                ->icon('heroicon-o-funnel')
                ->form([
                    Select::make('period')
                        ->label('Periode')
                        ->options([
                            '2025-9'  => 'September 2025',
                            '2025-10' => 'Oktober 2025',
                            '2025-11' => 'November 2025',
                            '2025-12' => 'Desember 2025',
                            '2026-1'  => 'Januari 2026',
                            '2026-2'  => 'Februari 2026',
                            '2026-3'  => 'Maret 2026',
                            '2026-4'  => 'April 2026',
                            '2026-5'  => 'Mei 2026',
                            '2026-6'  => 'Juni 2026',
                            '2026-7'  => 'Juli 2026',
                            '2026-8'  => 'Agustus 2026',
                            '2026-9'  => 'September 2026',
                            '2026-10' => 'Oktober 2026',
                            '2026-11' => 'November 2026',
                            '2026-12' => 'Desember 2026',
                        ])
                        ->default($this->year . '-' . $this->month)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $parts = explode('-', $data['period']);
                    $this->year  = (int) $parts[0];
                    $this->month = (int) $parts[1];
                    $this->redirect(static::getResource()::getUrl('neraca-lajur-piutang', [
                        'month' => $this->month,
                        'year'  => $this->year,
                    ]));
                }),
            Action::make('viewOld')
                ->label('Lihat Neraca Lajur (Lama)')
                ->icon('heroicon-o-arrow-left-circle')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('neraca-lajur', [
                    'month' => $this->month,
                    'year'  => $this->year,
                ])),
            Action::make('exportDetailJP')
                ->label('Export Detail Jurnal Pendapatan')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('info')
                ->url(fn () => url('/neraca-lajur-piutang/export-detail-jp?month=' . $this->month . '&year=' . $this->year))
                ->openUrlInNewTab(false),
            Action::make('export')
                ->label('Export Neraca Lajur')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => url('/neraca-lajur-piutang/export?month=' . $this->month . '&year=' . $this->year))
                ->openUrlInNewTab(false),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('index')),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers: hitung piutang dari MoU dan Memo
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Piutang dari MoU yang approved pada bulan berjalan.
     * Return: [ coa_id => total_amount ] untuk sisi Kredit Pendapatan,
     *         ditambah total keseluruhan untuk sisi Debit AO-103.
     */
    private function getMouPiutangByCoa(string $startOfMonth, string $endOfMonth): array
    {
        // Ambil nilai MoU per COA dari cost_list_mous
        $rows = DB::table('cost_list_mous as clm')
            ->join('mous as m', 'm.id', '=', 'clm.mou_id')
            ->whereNull('m.deleted_at')
            ->whereNull('clm.deleted_at')
            ->where('m.status', 'approved')
            ->where('m.type', 'kkp')
            ->whereBetween('m.approved_date', [$startOfMonth, $endOfMonth])
            ->groupBy('clm.coa_id')
            ->selectRaw('clm.coa_id, SUM(clm.total_amount) as total')
            ->get();

        $byCoa = [];
        $grandTotal = 0;
        foreach ($rows as $row) {
            $byCoa[$row->coa_id] = $row->total;
            $grandTotal += $row->total;
        }

        return ['by_coa' => $byCoa, 'total' => $grandTotal];
    }

    /**
     * Piutang dari Memo yang ditandatangani pada bulan berjalan.
     * Semua masuk ke COA AO-126 (Pendapatan Lain-lain, id=126).
     * Return: total nilai memo bulan ini.
     */
    private function getMemoPiutangTotal(string $startOfMonth, string $endOfMonth): float
    {
        return (float) DB::table('memos')
            ->whereNull('deleted_at')
            ->whereBetween('tanggal_ttd', [$startOfMonth, $endOfMonth])
            ->sum('total_fee');
    }

    /**
     * Realisasi dari Invoice yang diterbitkan bulan ini.
     * Return: [ coa_id => total_amount ] untuk sisi Debit Pendapatan (Invoice mengurangi piutang),
     *         ditambah total keseluruhan untuk sisi Kredit AO-103.
     */
    private function getInvoiceRealisasiByCoa(string $startOfMonth, string $endOfMonth): array
    {
        $rows = DB::table('cost_list_invoices as cli')
            ->join('invoices as i', 'i.id', '=', 'cli.invoice_id')
            ->whereNull('i.deleted_at')
            ->whereNull('cli.deleted_at')
            ->where('i.invoice_type', 'kkp')
            ->whereBetween('i.invoice_date', [$startOfMonth, $endOfMonth])
            ->groupBy('cli.coa_id')
            ->selectRaw('cli.coa_id, SUM(cli.amount) as total')
            ->get();

        $byCoa = [];
        $grandTotal = 0;
        foreach ($rows as $row) {
            $byCoa[$row->coa_id] = $row->total;
            $grandTotal += $row->total;
        }

        return ['by_coa' => $byCoa, 'total' => $grandTotal];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Data untuk laba rugi summary (header)
    // ──────────────────────────────────────────────────────────────────────────

    protected function getLabaRugiData(): array
    {
        $data = $this->getTableQuery()->get();
        $labaRugiData   = [];
        $totalPendapatan = 0;
        $totalBeban      = 0;

        foreach ($data as $row) {
            $totalDebit  = $row->neraca_awal_debit + $row->kas_besar_debit +
                           $row->kas_kecil_debit + $row->bank_debit +
                           $row->jurnal_pendapatan_debit + $row->jurnal_umum_debit;

            $totalKredit = $row->neraca_awal_kredit + $row->kas_besar_kredit +
                           $row->kas_kecil_kredit + $row->bank_kredit +
                           $row->jurnal_pendapatan_kredit + $row->jurnal_umum_kredit;

            $selisihSebelumAJE = $totalDebit - $totalKredit;
            $selisihSetelahAJE = $selisihSebelumAJE + ($row->aje_debit - $row->aje_kredit);

            if (in_array($row->group_coa_id, self::LABA_RUGI_GROUP_IDS, true)) {
                $amount = $selisihSetelahAJE;
                if (in_array($row->group_coa_id, self::LABA_RUGI_PENDAPATAN_GROUP_IDS, true)) {
                    $totalPendapatan += $amount;
                    $category = 'Pendapatan';
                } else {
                    $totalBeban += $amount;
                    $category = 'Beban';
                }
                $labaRugiData[] = [
                    'code'     => $row->code,
                    'name'     => $row->name,
                    'category' => $category,
                    'amount'   => abs($amount),
                    'is_debit' => $amount > 0,
                ];
            }
        }

        return [
            'items'          => $labaRugiData,
            'totalPendapatan' => abs($totalPendapatan),
            'totalBeban'      => abs($totalBeban),
            'labaRugiBersih'  => abs($totalPendapatan) - abs($totalBeban),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Query utama
    // ──────────────────────────────────────────────────────────────────────────

    protected function getTableQuery(): Builder
    {
        $startOfPreviousMonth = Carbon::create($this->year, $this->month, 1)->subMonth()->startOfMonth();
        $endOfPreviousMonth   = Carbon::create($this->year, $this->month, 1)->subMonth()->endOfMonth();
        $startOfCurrentMonth  = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfCurrentMonth    = Carbon::create($this->year, $this->month, 1)->endOfMonth();

        $depresiasiTotal = \App\Models\DepresiasiAktivaTetap::query()
            ->whereYear('tanggal_penyusutan', '=', $this->year, 'and')
            ->whereMonth('tanggal_penyusutan', '=', $this->month, 'and')
            ->sum('jumlah_penyusutan') ?? 0;

        // Hitung piutang MoU
        $mouData     = $this->getMouPiutangByCoa($startOfCurrentMonth, $endOfCurrentMonth);
        $mouByCoa    = $mouData['by_coa'];      // [coa_id => total] → Kredit Pendapatan
        $mouTotal    = $mouData['total'];        // → Debit AO-103

        // Hitung piutang Memo (semua ke AO-126, id=126)
        $memoTotal   = $this->getMemoPiutangTotal($startOfCurrentMonth, $endOfCurrentMonth);

        // Hitung realisasi Invoice
        $invData     = $this->getInvoiceRealisasiByCoa($startOfCurrentMonth, $endOfCurrentMonth);
        $invByCoa    = $invData['by_coa'];      // [coa_id => total] → Debit Pendapatan
        $invTotal    = $invData['total'];        // → Kredit AO-103

        // COA AO-126 id
        $coa126Id    = 126;

        // Total piutang debit untuk AO-103
        $piutangDebitTotal  = $mouTotal + $memoTotal;
        // Total realisasi kredit untuk AO-103
        $piutangKreditTotal = $invTotal;

        // Build CASE WHEN SQL untuk Jurnal Pendapatan Debit (per COA)
        // AO-103 → piutangDebitTotal (MoU + Memo)
        // COA pendapatan (dari invoice) → invByCoa[coa_id]
        $coaPiutangId = self::COA_PIUTANG_USAHA_ID;

        // Build CASE expression untuk debit
        $debitCases = "CASE coa.id WHEN {$coaPiutangId} THEN {$piutangDebitTotal}";
        foreach ($invByCoa as $coaId => $total) {
            $debitCases .= " WHEN {$coaId} THEN {$total}";
        }
        $debitCases .= " ELSE 0 END";

        // Build CASE expression untuk kredit
        // AO-103 → piutangKreditTotal (invoice)
        // COA pendapatan (dari MoU/Memo) → mouByCoa + memoTotal untuk AO-126
        $mouByCoa[$coa126Id] = ($mouByCoa[$coa126Id] ?? 0) + $memoTotal;

        $kreditCases = "CASE coa.id WHEN {$coaPiutangId} THEN {$piutangKreditTotal}";
        foreach ($mouByCoa as $coaId => $total) {
            $kreditCases .= " WHEN {$coaId} THEN {$total}";
        }
        $kreditCases .= " ELSE 0 END";

        $query = Coa::query()
            ->select([
                'coa.id',
                'coa.code',
                'coa.name',
                'coa.type',
                'coa.group_coa_id',
                DB::raw('COALESCE(journal_data.neraca_awal_debit, 0) as neraca_awal_debit'),
                DB::raw('COALESCE(journal_data.neraca_awal_kredit, 0) as neraca_awal_kredit'),
                DB::raw('COALESCE(kas_besar_data.kas_besar_debit, 0) as kas_besar_debit'),
                DB::raw('COALESCE(kas_besar_data.kas_besar_kredit, 0) as kas_besar_kredit'),
                DB::raw('COALESCE(kas_kecil_data.kas_kecil_debit, 0) as kas_kecil_debit'),
                DB::raw('COALESCE(kas_kecil_data.kas_kecil_kredit, 0) as kas_kecil_kredit'),
                DB::raw('COALESCE(bank_data.bank_debit, 0) as bank_debit'),
                DB::raw('COALESCE(bank_data.bank_kredit, 0) as bank_kredit'),
                // Jurnal Pendapatan: dari MoU/Memo/Invoice (bukan journal_book_reports)
                DB::raw("({$debitCases}) as jurnal_pendapatan_debit"),
                DB::raw("({$kreditCases}) as jurnal_pendapatan_kredit"),
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_debit, 0) as jurnal_umum_debit'),
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_kredit, 0) as jurnal_umum_kredit'),
                DB::raw("COALESCE(aje_data.aje_debit, 0) + (CASE WHEN coa.code = 'AO-509' THEN {$depresiasiTotal} ELSE 0 END) as aje_debit"),
                DB::raw("COALESCE(aje_data.aje_kredit, 0) + (CASE WHEN coa.code = 'AO-127' THEN {$depresiasiTotal} ELSE 0 END) as aje_kredit"),
                DB::raw('COALESCE(neraca_awal_bulan_depan.neraca_awal_bulan_depan_debit, 0) as neraca_awal_bulan_depan_debit'),
                DB::raw('COALESCE(neraca_awal_bulan_depan.neraca_awal_bulan_depan_kredit, 0) as neraca_awal_bulan_depan_kredit'),
            ])
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        SUM(debit_amount) as neraca_awal_debit,
                        SUM(credit_amount) as neraca_awal_kredit
                    FROM journal_book_reports
                    WHERE transaction_date BETWEEN '{$startOfPreviousMonth}' AND '{$endOfPreviousMonth}'
                    AND deleted_at IS NULL
                    AND journal_book_id = 3
                    GROUP BY coa_id
                ) as journal_data"),
                'coa.id', '=', 'journal_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        debit_amount as kas_besar_kredit,
                        credit_amount as kas_besar_debit
                    FROM (
                        SELECT
                            coa_id,
                            SUM(debit_amount) as debit_amount,
                            SUM(credit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 6
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                        AND deleted_at IS NULL
                        AND coa_id != 76
                        GROUP BY coa_id

                        UNION ALL

                        SELECT
                            76 as coa_id,
                            SUM(credit_amount) as debit_amount,
                            SUM(debit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 6
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                        AND deleted_at IS NULL
                    ) as combined_data
                ) as kas_besar_data"),
                'coa.id', '=', 'kas_besar_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        debit_amount as kas_kecil_debit,
                        credit_amount as kas_kecil_kredit
                    FROM (
                        SELECT
                            coa_id,
                            SUM(credit_amount) as debit_amount,
                            SUM(debit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 7
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                        AND deleted_at IS NULL
                        AND coa_id != 77
                        GROUP BY coa_id

                        UNION ALL

                        SELECT
                            77 as coa_id,
                            SUM(debit_amount) as debit_amount,
                            SUM(credit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 7
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                        AND deleted_at IS NULL
                    ) as combined_data
                ) as kas_kecil_data"),
                'coa.id', '=', 'kas_kecil_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        c.id as coa_id,
                        CASE
                            WHEN c.code = 'AO-1010' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id = 162 AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-101.2' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id IN (78) AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-102.1' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=1 AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.2' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=3 AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.3' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=2 AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.4' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=4 AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.5' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=5 AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                            ELSE (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE coa_id=c.id AND cash_reference_id IN (1,2,3,4,5) AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                        END as bank_kredit,
                        CASE
                            WHEN c.code = 'AO-1010' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id = 162 AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-101.2' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id IN (78) AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-102.1' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=1 AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.2' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=3 AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.3' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=2 AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.4' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=4 AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.5' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=5 AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                            ELSE (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE coa_id=c.id AND cash_reference_id IN (1,2,3,4,5) AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}' AND deleted_at IS NULL)
                        END as bank_debit
                    FROM coa c
                ) as bank_data"),
                'coa.id', '=', 'bank_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        SUM(debit_amount) as jurnal_umum_debit,
                        SUM(credit_amount) as jurnal_umum_kredit
                    FROM journal_book_reports
                    WHERE journal_book_id = 1
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as jurnal_umum_data"),
                'coa.id', '=', 'jurnal_umum_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        SUM(debit_amount) as aje_debit,
                        SUM(credit_amount) as aje_kredit
                    FROM journal_book_reports
                    WHERE journal_book_id = 2
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as aje_data"),
                'coa.id', '=', 'aje_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        SUM(debit_amount) as neraca_awal_bulan_depan_debit,
                        SUM(credit_amount) as neraca_awal_bulan_depan_kredit
                    FROM journal_book_reports
                    WHERE transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    AND journal_book_id = 3
                    GROUP BY coa_id
                ) as neraca_awal_bulan_depan"),
                'coa.id', '=', 'neraca_awal_bulan_depan.coa_id'
            )
            ->where('coa.deleted_at', null)
            ->where('coa.type', 'kkp')
            ->orderBy('coa.group_coa_id')
            ->orderBy('coa.code');

        return $query;
    }

    public function getTitle(): string
    {
        $monthName = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return 'Neraca Lajur Bulanan (Konsep Piutang) - ' . $monthName[$this->month] . ' ' . $this->year;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Export Detail Jurnal Pendapatan (MoU / Memo / Invoice)
    // ──────────────────────────────────────────────────────────────────────────

    public function exportDetailJurnalPendapatan()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        $startOfMonth = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfMonth   = Carbon::create($this->year, $this->month, 1)->endOfMonth();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $periodeLabel = $monthNames[$this->month] . ' ' . $this->year;

        $headerStyle = [
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
        ];
        $subHeaderStyle = array_merge($headerStyle, ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']]]);
        $totalStyle = [
            'font'    => ['bold' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE4D6']],
        ];
        $numberFmt = '#,##0';

        $spreadsheet = new Spreadsheet();

        // ──────────────────────────────────────────────────────────────────────
        // SHEET 1: RINGKASAN per COA
        // ──────────────────────────────────────────────────────────────────────
        $sheetSum = $spreadsheet->getActiveSheet();
        $sheetSum->setTitle('Ringkasan JP');

        $sheetSum->setCellValue('A1', 'DETAIL JURNAL PENDAPATAN (KONSEP PIUTANG) - ' . strtoupper($periodeLabel));
        $sheetSum->mergeCells('A1:G1');
        $sheetSum->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheetSum->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheetSum->setCellValue('A3', 'Kode COA');
        $sheetSum->setCellValue('B3', 'Nama COA');
        $sheetSum->setCellValue('C3', 'MoU (Piutang Kredit)');
        $sheetSum->setCellValue('D3', 'Memo (Piutang Kredit)');
        $sheetSum->setCellValue('E3', 'Invoice (Realisasi Debit)');
        $sheetSum->setCellValue('F3', 'Net Debit JP');
        $sheetSum->setCellValue('G3', 'Net Kredit JP');
        $sheetSum->getStyle('A3:G3')->applyFromArray($headerStyle);

        // Data MoU per COA
        $mouRows = DB::table('cost_list_mous as clm')
            ->join('mous as m', 'm.id', '=', 'clm.mou_id')
            ->join('coa', 'coa.id', '=', 'clm.coa_id')
            ->whereNull('m.deleted_at')
            ->whereNull('clm.deleted_at')
            ->where('m.status', 'approved')
            ->where('m.type', 'kkp')
            ->whereBetween('m.approved_date', [$startOfMonth, $endOfMonth])
            ->groupBy('clm.coa_id', 'coa.code', 'coa.name')
            ->selectRaw('clm.coa_id, coa.code, coa.name, SUM(clm.total_amount) as total_mou')
            ->get()
            ->keyBy('coa_id');

        $memoTotal = $this->getMemoPiutangTotal($startOfMonth, $endOfMonth);

        // Data Invoice per COA
        $invRows = DB::table('cost_list_invoices as cli')
            ->join('invoices as i', 'i.id', '=', 'cli.invoice_id')
            ->join('coa', 'coa.id', '=', 'cli.coa_id')
            ->whereNull('i.deleted_at')
            ->whereNull('cli.deleted_at')
            ->where('i.invoice_type', 'kkp')
            ->whereBetween('i.invoice_date', [$startOfMonth, $endOfMonth])
            ->groupBy('cli.coa_id', 'coa.code', 'coa.name')
            ->selectRaw('cli.coa_id, coa.code, coa.name, SUM(cli.amount) as total_inv')
            ->get()
            ->keyBy('coa_id');

        // Ambil COA Piutang Usaha
        $coaPiutang = DB::table('coa')->where('id', self::COA_PIUTANG_USAHA_ID)->first();
        $coaMap = [];

        // Kumpulkan semua coa_id yang terlibat
        $allCoaIds = collect(array_keys($mouRows->toArray()))
            ->merge(array_keys($invRows->toArray()))
            ->push(126) // AO-126 untuk Memo
            ->unique();

        $coaNames = DB::table('coa')->whereIn('id', $allCoaIds)->get()->keyBy('id');

        $sumRow = 4;
        $sumMouTotal = 0; $sumMemoTotal = 0; $sumInvTotal = 0;

        // Baris AO-103 (Piutang Usaha)
        $mouGrandTotal = $mouRows->sum('total_mou');
        $invGrandTotal = $invRows->sum('total_inv');
        $piutangDebit  = $mouGrandTotal + $memoTotal;
        $piutangKredit = $invGrandTotal;

        $sheetSum->setCellValue('A' . $sumRow, $coaPiutang ? $coaPiutang->code : 'AO-103');
        $sheetSum->setCellValue('B' . $sumRow, $coaPiutang ? $coaPiutang->name : 'Piutang Usaha');
        $sheetSum->setCellValue('C' . $sumRow, $mouGrandTotal);
        $sheetSum->setCellValue('D' . $sumRow, $memoTotal);
        $sheetSum->setCellValue('E' . $sumRow, '');
        $sheetSum->setCellValue('F' . $sumRow, $piutangDebit);
        $sheetSum->setCellValue('G' . $sumRow, $piutangKredit);
        $sheetSum->getStyle('C' . $sumRow . ':G' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sumRow++;

        foreach ($allCoaIds as $coaId) {
            $coa     = $coaNames->get($coaId);
            $mouVal  = $mouRows->has($coaId) ? $mouRows->get($coaId)->total_mou : 0;
            $memoVal = ($coaId == 126) ? $memoTotal : 0;  // Memo ke AO-126
            $invVal  = $invRows->has($coaId) ? $invRows->get($coaId)->total_inv : 0;

            // Kredit: MoU + Memo; Debit: Invoice
            $netDebit  = $invVal;
            $netKredit = $mouVal + $memoVal;

            $sumMouTotal  += $mouVal;
            $sumMemoTotal += $memoVal;
            $sumInvTotal  += $invVal;

            $sheetSum->setCellValue('A' . $sumRow, $coa ? $coa->code : $coaId);
            $sheetSum->setCellValue('B' . $sumRow, $coa ? $coa->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, $mouVal ?: '');
            $sheetSum->setCellValue('D' . $sumRow, $memoVal ?: '');
            $sheetSum->setCellValue('E' . $sumRow, $invVal ?: '');
            $sheetSum->setCellValue('F' . $sumRow, $netDebit ?: '');
            $sheetSum->setCellValue('G' . $sumRow, $netKredit ?: '');
            $sheetSum->getStyle('C' . $sumRow . ':G' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $sumRow++;
        }

        // Total
        $sheetSum->setCellValue('A' . $sumRow, 'TOTAL');
        $sheetSum->setCellValue('B' . $sumRow, '');
        $sheetSum->setCellValue('C' . $sumRow, $sumMouTotal);
        $sheetSum->setCellValue('D' . $sumRow, $sumMemoTotal);
        $sheetSum->setCellValue('E' . $sumRow, $sumInvTotal);
        $sheetSum->setCellValue('F' . $sumRow, $sumInvTotal);
        $sheetSum->setCellValue('G' . $sumRow, $sumMouTotal + $sumMemoTotal);
        $sheetSum->getStyle('A' . $sumRow . ':G' . $sumRow)->applyFromArray($totalStyle);
        $sheetSum->getStyle('C' . $sumRow . ':G' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);

        $sheetSum->getStyle('A3:G' . $sumRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        foreach (range('A', 'G') as $col) { $sheetSum->getColumnDimension($col)->setAutoSize(true); }

        // ──────────────────────────────────────────────────────────────────────
        // SHEET 2: DETAIL MoU
        // ──────────────────────────────────────────────────────────────────────
        $sheetMou = $spreadsheet->createSheet();
        $sheetMou->setTitle('MoU (Piutang)');

        $sheetMou->setCellValue('A1', 'DAFTAR MoU KKP APPROVED - ' . strtoupper($periodeLabel));
        $sheetMou->mergeCells('A1:H1');
        $sheetMou->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheetMou->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $mou_headers = ['A3' => 'No. MoU', 'B3' => 'Approved Date', 'C3' => 'Client', 'D3' => 'Status',
                        'E3' => 'Kode COA', 'F3' => 'Nama COA', 'G3' => 'Total Amount', 'H3' => 'Keterangan'];
        foreach ($mou_headers as $cell => $label) {
            $sheetMou->setCellValue($cell, $label);
        }
        $sheetMou->getStyle('A3:H3')->applyFromArray($headerStyle);

        $mouDetail = DB::table('cost_list_mous as clm')
            ->join('mous as m', 'm.id', '=', 'clm.mou_id')
            ->join('coa', 'coa.id', '=', 'clm.coa_id')
            ->leftJoin('clients', 'clients.id', '=', 'm.client_id')
            ->whereNull('m.deleted_at')
            ->whereNull('clm.deleted_at')
            ->where('m.status', 'approved')
            ->where('m.type', 'kkp')
            ->whereBetween('m.approved_date', [$startOfMonth, $endOfMonth])
            ->orderBy('m.approved_date')
            ->orderBy('m.mou_number')
            ->selectRaw('
                m.mou_number,
                m.approved_date,
                COALESCE(clients.company_name, "-") as client_name,
                m.status,
                coa.code as coa_code,
                coa.name as coa_name,
                clm.total_amount,
                clm.description
            ')
            ->get();

        $mouRow = 4;
        $mouGrand = 0;
        foreach ($mouDetail as $m) {
            $sheetMou->setCellValue('A' . $mouRow, $m->mou_number);
            $sheetMou->setCellValue('B' . $mouRow, $m->approved_date);
            $sheetMou->setCellValue('C' . $mouRow, $m->client_name);
            $sheetMou->setCellValue('D' . $mouRow, $m->status);
            $sheetMou->setCellValue('E' . $mouRow, $m->coa_code);
            $sheetMou->setCellValue('F' . $mouRow, $m->coa_name);
            $sheetMou->setCellValue('G' . $mouRow, $m->total_amount);
            $sheetMou->setCellValue('H' . $mouRow, $m->description);
            $sheetMou->getStyle('G' . $mouRow)->getNumberFormat()->setFormatCode($numberFmt);
            $mouGrand += $m->total_amount;
            $mouRow++;
        }
        $sheetMou->setCellValue('F' . $mouRow, 'TOTAL');
        $sheetMou->setCellValue('G' . $mouRow, $mouGrand);
        $sheetMou->getStyle('A' . $mouRow . ':H' . $mouRow)->applyFromArray($totalStyle);
        $sheetMou->getStyle('G' . $mouRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sheetMou->getStyle('A3:H' . $mouRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
        foreach (range('A', 'H') as $col) { $sheetMou->getColumnDimension($col)->setAutoSize(true); }

        // ──────────────────────────────────────────────────────────────────────
        // SHEET 3: DETAIL MEMO
        // ──────────────────────────────────────────────────────────────────────
        $sheetMemo = $spreadsheet->createSheet();
        $sheetMemo->setTitle('Memo (Piutang)');

        $sheetMemo->setCellValue('A1', 'DAFTAR MEMO - ' . strtoupper($periodeLabel));
        $sheetMemo->mergeCells('A1:F1');
        $sheetMemo->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheetMemo->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $memo_headers = ['A3' => 'No. Memo', 'B3' => 'Tanggal TTD', 'C3' => 'Nama Klien',
                         'D3' => 'Total Fee', 'E3' => 'COA', 'F3' => 'Keterangan'];
        foreach ($memo_headers as $cell => $label) {
            $sheetMemo->setCellValue($cell, $label);
        }
        $sheetMemo->getStyle('A3:F3')->applyFromArray($headerStyle);

        $memoDetail = DB::table('memos')
            ->whereNull('deleted_at')
            ->whereBetween('tanggal_ttd', [$startOfMonth, $endOfMonth])
            ->orderBy('tanggal_ttd')
            ->get();

        $memoRow = 4;
        $memoGrand = 0;
        foreach ($memoDetail as $memo) {
            $sheetMemo->setCellValue('A' . $memoRow, $memo->no_memo);
            $sheetMemo->setCellValue('B' . $memoRow, $memo->tanggal_ttd);
            $sheetMemo->setCellValue('C' . $memoRow, $memo->nama_klien);
            $sheetMemo->setCellValue('D' . $memoRow, $memo->total_fee);
            $sheetMemo->setCellValue('E' . $memoRow, 'AO-126 - Pendapatan Lain-lain');
            $sheetMemo->setCellValue('F' . $memoRow, $memo->description);
            $sheetMemo->getStyle('D' . $memoRow)->getNumberFormat()->setFormatCode($numberFmt);
            $memoGrand += $memo->total_fee;
            $memoRow++;
        }
        $sheetMemo->setCellValue('C' . $memoRow, 'TOTAL');
        $sheetMemo->setCellValue('D' . $memoRow, $memoGrand);
        $sheetMemo->getStyle('A' . $memoRow . ':F' . $memoRow)->applyFromArray($totalStyle);
        $sheetMemo->getStyle('D' . $memoRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sheetMemo->getStyle('A3:F' . $memoRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
        foreach (range('A', 'F') as $col) { $sheetMemo->getColumnDimension($col)->setAutoSize(true); }

        // ──────────────────────────────────────────────────────────────────────
        // SHEET 4: DETAIL INVOICE
        // ──────────────────────────────────────────────────────────────────────
        $sheetInv = $spreadsheet->createSheet();
        $sheetInv->setTitle('Invoice (Realisasi)');

        $sheetInv->setCellValue('A1', 'DAFTAR INVOICE KKP - ' . strtoupper($periodeLabel));
        $sheetInv->mergeCells('A1:I1');
        $sheetInv->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheetInv->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $inv_headers = ['A3' => 'No. Invoice', 'B3' => 'Tanggal Invoice', 'C3' => 'Status',
                        'D3' => 'No. MoU / Memo', 'E3' => 'Client',
                        'F3' => 'Kode COA', 'G3' => 'Nama COA', 'H3' => 'Amount', 'I3' => 'Keterangan'];
        foreach ($inv_headers as $cell => $label) {
            $sheetInv->setCellValue($cell, $label);
        }
        $sheetInv->getStyle('A3:I3')->applyFromArray($headerStyle);

        $invDetail = DB::table('cost_list_invoices as cli')
            ->join('invoices as i', 'i.id', '=', 'cli.invoice_id')
            ->join('coa', 'coa.id', '=', 'cli.coa_id')
            ->leftJoin('mous as m', 'm.id', '=', 'i.mou_id')
            ->leftJoin('memos as mem', 'mem.id', '=', 'i.memo_id')
            ->leftJoin('clients', 'clients.id', '=', 'i.client_id')
            ->whereNull('i.deleted_at')
            ->whereNull('cli.deleted_at')
            ->where('i.invoice_type', 'kkp')
            ->whereBetween('i.invoice_date', [$startOfMonth, $endOfMonth])
            ->orderBy('i.invoice_date')
            ->orderBy('i.invoice_number')
            ->selectRaw('
                i.invoice_number,
                i.invoice_date,
                i.invoice_status,
                COALESCE(m.mou_number, mem.no_memo, "-") as referensi,
                COALESCE(clients.company_name, "-") as client_name,
                coa.code as coa_code,
                coa.name as coa_name,
                cli.amount,
                cli.description
            ')
            ->get();

        $invRow = 4;
        $invGrand2 = 0;
        foreach ($invDetail as $inv) {
            $sheetInv->setCellValue('A' . $invRow, $inv->invoice_number);
            $sheetInv->setCellValue('B' . $invRow, $inv->invoice_date);
            $sheetInv->setCellValue('C' . $invRow, $inv->invoice_status);
            $sheetInv->setCellValue('D' . $invRow, $inv->referensi);
            $sheetInv->setCellValue('E' . $invRow, $inv->client_name);
            $sheetInv->setCellValue('F' . $invRow, $inv->coa_code);
            $sheetInv->setCellValue('G' . $invRow, $inv->coa_name);
            $sheetInv->setCellValue('H' . $invRow, $inv->amount);
            $sheetInv->setCellValue('I' . $invRow, $inv->description);
            $sheetInv->getStyle('H' . $invRow)->getNumberFormat()->setFormatCode($numberFmt);
            $invGrand2 += $inv->amount;
            $invRow++;
        }
        $sheetInv->setCellValue('G' . $invRow, 'TOTAL');
        $sheetInv->setCellValue('H' . $invRow, $invGrand2);
        $sheetInv->getStyle('A' . $invRow . ':I' . $invRow)->applyFromArray($totalStyle);
        $sheetInv->getStyle('H' . $invRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sheetInv->getStyle('A3:I' . $invRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
        foreach (range('A', 'I') as $col) { $sheetInv->getColumnDimension($col)->setAutoSize(true); }

        // Set active sheet ke ringkasan
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'detail-jp-piutang-' . strtolower(Carbon::create($this->year, $this->month, 1)->format('F-Y')) . '.xlsx';

        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Export Excel (Neraca Lajur)
    // ──────────────────────────────────────────────────────────────────────────

    public function exportToExcel()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $this->getTitle());
        $sheet->mergeCells('A1:W1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Kode Akun');
        $headers = ['B3' => 'Neraca Awal', 'D3' => 'Kas Besar', 'F3' => 'Kas Kecil', 'H3' => 'Bank',
                    'J3' => 'Jurnal Pendapatan (Piutang)', 'L3' => 'Jurnal Umum',
                    'N3' => 'Neraca Sebelum AJE', 'P3' => 'AJE',
                    'R3' => 'Neraca Setelah AJE', 'T3' => 'Neraca', 'V3' => 'Laba Rugi'];
        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        $cols = ['B', 'D', 'F', 'H', 'J', 'L', 'N', 'P', 'R', 'T', 'V'];
        foreach ($cols as $col) {
            $sheet->setCellValue($col . '4', 'Debit');
            $sheet->setCellValue(chr(ord($col) + 1) . '4', 'Kredit');
        }

        $sheet->mergeCells('A3:A4');
        foreach ($cols as $col) {
            $sheet->mergeCells($col . '3:' . chr(ord($col) + 1) . '3');
        }

        $headerStyle = [
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C3C1C1']],
        ];
        $sheet->getStyle('A3:W4')->applyFromArray($headerStyle);

        $data = $this->getTableQuery()->get();
        $row  = 5;

        foreach ($data as $item) {
            $totalDebit  = $item->neraca_awal_debit + $item->kas_besar_debit + $item->kas_kecil_debit +
                           $item->bank_debit + $item->jurnal_pendapatan_debit + $item->jurnal_umum_debit;
            $totalKredit = $item->neraca_awal_kredit + $item->kas_besar_kredit + $item->kas_kecil_kredit +
                           $item->bank_kredit + $item->jurnal_pendapatan_kredit + $item->jurnal_umum_kredit;

            $selisihSebelumAJE    = $totalDebit - $totalKredit;
            $neracaSebelumAJEDebit  = max(0, $selisihSebelumAJE);
            $neracaSebelumAJEKredit = max(0, -$selisihSebelumAJE);

            $selisihSetelahAJE    = $selisihSebelumAJE + ($item->aje_debit - $item->aje_kredit);
            $neracaSetelahAJEDebit  = max(0, $selisihSetelahAJE);
            $neracaSetelahAJEKredit = max(0, -$selisihSetelahAJE);

            $showInNeraca  = in_array($item->group_coa_id, self::NERACA_GROUP_IDS, true);
            $neracaDebit   = $showInNeraca ? $neracaSetelahAJEDebit : 0;
            $neracaKredit  = $showInNeraca ? $neracaSetelahAJEKredit : 0;

            $showInLabaRugi = in_array($item->group_coa_id, self::LABA_RUGI_GROUP_IDS, true);
            $labaRugiDebit  = $showInLabaRugi ? $neracaSetelahAJEDebit : 0;
            $labaRugiKredit = $showInLabaRugi ? $neracaSetelahAJEKredit : 0;

            $sheet->setCellValue('A' . $row, $item->code . ' ' . $item->name);
            $sheet->setCellValue('B' . $row, $item->neraca_awal_debit);
            $sheet->setCellValue('C' . $row, $item->neraca_awal_kredit);
            $sheet->setCellValue('D' . $row, $item->kas_besar_debit);
            $sheet->setCellValue('E' . $row, $item->kas_besar_kredit);
            $sheet->setCellValue('F' . $row, $item->kas_kecil_debit);
            $sheet->setCellValue('G' . $row, $item->kas_kecil_kredit);
            $sheet->setCellValue('H' . $row, $item->bank_debit);
            $sheet->setCellValue('I' . $row, $item->bank_kredit);
            $sheet->setCellValue('J' . $row, $item->jurnal_pendapatan_debit);
            $sheet->setCellValue('K' . $row, $item->jurnal_pendapatan_kredit);
            $sheet->setCellValue('L' . $row, $item->jurnal_umum_debit);
            $sheet->setCellValue('M' . $row, $item->jurnal_umum_kredit);
            $sheet->setCellValue('N' . $row, $neracaSebelumAJEDebit);
            $sheet->setCellValue('O' . $row, $neracaSebelumAJEKredit);
            $sheet->setCellValue('P' . $row, $item->aje_debit);
            $sheet->setCellValue('Q' . $row, $item->aje_kredit);
            $sheet->setCellValue('R' . $row, $neracaSetelahAJEDebit);
            $sheet->setCellValue('S' . $row, $neracaSetelahAJEKredit);
            $sheet->setCellValue('T' . $row, $neracaDebit);
            $sheet->setCellValue('U' . $row, $neracaKredit);
            $sheet->setCellValue('V' . $row, $labaRugiDebit);
            $sheet->setCellValue('W' . $row, $labaRugiKredit);
            $row++;
        }

        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'Total');
        $sheet->getStyle('A' . $totalRow . ':W' . $totalRow)->applyFromArray($headerStyle);
        $columns = range('B', 'W');
        foreach ($columns as $col) {
            $sheet->setCellValue($col . $totalRow, '=SUM(' . $col . '5:' . $col . ($totalRow - 1) . ')');
        }
        $sheet->getStyle('A5:W' . ($totalRow - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getStyle('B5:W' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');

        foreach (range('A', 'W') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'neraca-lajur-piutang-' . strtolower(Carbon::create($this->year, $this->month, 1)->format('F-Y')) . '.xlsx';

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
