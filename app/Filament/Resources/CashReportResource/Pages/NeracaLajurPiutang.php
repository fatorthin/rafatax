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
 * Kolom Jurnal Pendapatan pada halaman ini menggunakan sumber data dari cash_reports:
 *  - Jika di kolom kas/bank terdapat CoA Piutang (AO-103.x), maka:
 *      Kredit Jurnal Pendapatan : COA Pendapatan (AO-401.x) = nilai piutang per COA
 *      Debit  Jurnal Pendapatan : AO-208 (Pendapatan Yang Belum Diterima) = total nilai piutang
 *
 * Konsep jurnal:
 *   Saat ada penerimaan piutang di kas/bank (cash_reports dengan coa_id piutang AO-103.x):
 *     Kredit : COA Pendapatan AO-401.x = nilai piutang per sub-COA
 *     Debit  : AO-208 (Pendapatan Yang Belum Diterima) = total nilai pendapatan (sebagai pengurang)
 */
class NeracaLajurPiutang extends Page implements HasTable
{
    use InteractsWithTable;

    private const NERACA_GROUP_IDS = [10, 11, 12, 20, 21, 30];
    private const LABA_RUGI_GROUP_IDS = [40, 50, 60, 70];
    private const LABA_RUGI_PENDAPATAN_GROUP_IDS = [40, 60];

    // COA ID untuk Piutang Usaha (AO-103)
    private const COA_PIUTANG_USAHA_ID = 179;

    // COA ID untuk Pendapatan Yang Belum Diterima (AO-208)
    private const COA_PENDAPATAN_BELUM_DITERIMA_ID = 175;

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
                ->url(fn() => static::getResource()::getUrl('neraca-lajur', [
                    'month' => $this->month,
                    'year'  => $this->year,
                ])),
            Action::make('exportDetailJP')
                ->label('Export Detail Jurnal Pendapatan')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('info')
                ->url(fn() => url('/neraca-lajur-piutang/export-detail-jp?month=' . $this->month . '&year=' . $this->year))
                ->openUrlInNewTab(false),
            Action::make('export')
                ->label('Export Neraca Lajur')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn() => url('/neraca-lajur-piutang/export?month=' . $this->month . '&year=' . $this->year))
                ->openUrlInNewTab(false),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => static::getResource()::getUrl('index')),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers: Jurnal Pendapatan (JP)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Mapping CoA Piutang (AO-103.x) ke CoA Pendapatan (AO-401.x).
     */
    private function getPiutangToPendapatanMap(): array
    {
        return [
            188 => 119, // AO-103.6  -> AO-401   (Fee Bulanan)
            182 => 120, // AO-103.7  -> AO-401.1 (Fee SPT)
            183 => 121, // AO-103.8  -> AO-401.2 (Fee SP2DK)
            184 => 122, // AO-103.9  -> AO-401.3 (Fee Pembetulan)
            185 => 123, // AO-103.10 -> AO-401.4 (Fee Internal)
            186 => 124, // AO-103.11 -> AO-401.5 (Fee Restitusi)
            187 => 125, // AO-103.12 -> AO-401.6 (Fee Pemeriksaan)
        ];
    }

    /**
     * Sumber 1 — cost_list_mous (MoU approved bulan berjalan).
     *
     * Entri JP yang dihasilkan:
     *   JP DEBIT  : AO-103.x (piutang) = nilai per CoA dari MoU
     *   JP KREDIT : AO-208 (Pendapatan Belum Diterima) = total semua MoU
     *
     * Return:
     *   - by_piutang_coa : [ piutang_coa_id => total ]  -> JP DEBIT  AO-103.x
     *   - total          : grand total                   -> JP KREDIT AO-208
     */
    private function getMouPiutangForJP(string $startOfMonth, string $endOfMonth): array
    {
        $rows = DB::table('cost_list_mous as clm')
            ->join('mous as m', 'm.id', '=', 'clm.mou_id')
            ->whereNull('m.deleted_at')
            ->whereNull('clm.deleted_at')
            ->where('m.status', 'approved')
            ->where('m.type', 'kkp')
            ->whereBetween('m.approved_date', [$startOfMonth, $endOfMonth])
            ->whereIn('clm.coa_id', array_keys($this->getPiutangToPendapatanMap()))
            ->groupBy('clm.coa_id')
            ->selectRaw('clm.coa_id, SUM(clm.total_amount) as total')
            ->get();

        $byPiutangCoa = [];
        $grandTotal   = 0;

        foreach ($rows as $row) {
            $byPiutangCoa[$row->coa_id] = ($byPiutangCoa[$row->coa_id] ?? 0) + $row->total;
            $grandTotal                 += $row->total;
        }

        return [
            'by_piutang_coa' => $byPiutangCoa, // JP DEBIT  AO-103.x
            'total'          => $grandTotal,    // JP KREDIT AO-208
        ];
    }

    /**
     * Sumber 2 — cash_reports (CoA AO-103.x yang muncul di kolom kas/bank).
     *
     * Ketika piutang diterima di kas/bank, dua entri JP dihasilkan:
     *   JP DEBIT  : AO-208 (Pendapatan Belum Diterima) = total kas diterima (pengurang accrual)
     *   JP KREDIT : AO-401.x (Pendapatan)              = nilai per CoA (pengakuan pendapatan aktual)
     *
     * Return:
     *   - by_pendapatan_coa : [ pendapatan_coa_id => total ] -> JP KREDIT AO-401.x
     *   - total             : grand total kas diterima       -> JP DEBIT  AO-208
     */
    private function getCashReportPiutangForJP(string $startOfMonth, string $endOfMonth): array
    {
        $map  = $this->getPiutangToPendapatanMap();
        $rows = DB::table('cash_reports')
            ->whereNull('deleted_at')
            ->whereIn('coa_id', array_keys($map))
            ->whereIn('cash_reference_id', [1, 2, 3, 4, 5, 6, 7]) // bank + kas besar + kas kecil
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->groupBy('coa_id')
            ->selectRaw('coa_id, SUM(debit_amount) as total')
            ->get();

        $byPendapatanCoa = [];
        $grandTotal      = 0;

        foreach ($rows as $row) {
            $pendapatanCoaId = $map[$row->coa_id] ?? null;
            if ($pendapatanCoaId) {
                $byPendapatanCoa[$pendapatanCoaId] = ($byPendapatanCoa[$pendapatanCoaId] ?? 0) + $row->total;
                $grandTotal                        += $row->total;
            }
        }

        return [
            'by_pendapatan_coa' => $byPendapatanCoa, // JP KREDIT AO-401.x
            'total'             => $grandTotal,       // JP DEBIT  AO-208
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Data untuk laba rugi summary (header)
    // ──────────────────────────────────────────────────────────────────────────

    protected function getLabaRugiData(): array
    {
        $data = $this->getTableData();
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

    public function getTableData()
    {
        $data = $this->getTableQuery()->get();

        $targetCodes = ['AO-103.5', 'AO-103.6', 'AO-103.7', 'AO-103.8', 'AO-103.9', 'AO-103.10', 'AO-103.11', 'AO-103.12'];
        $subRows = $data->filter(fn($row) => in_array($row->code, $targetCodes));
        $mainRow = $data->first(fn($row) => $row->code === 'AO-103');

        if ($mainRow && $subRows->isNotEmpty()) {
            $columnsToSum = [
                'kas_besar_debit', 'kas_besar_kredit',
                'kas_kecil_debit', 'kas_kecil_kredit',
                'bank_debit', 'bank_kredit',
                'jurnal_pendapatan_debit', 'jurnal_pendapatan_kredit',
                'jurnal_umum_debit', 'jurnal_umum_kredit',
                'aje_debit', 'aje_kredit',
                'neraca_awal_bulan_depan_debit', 'neraca_awal_bulan_depan_kredit'
            ];

            foreach ($columnsToSum as $col) {
                $mainRow->$col = $subRows->sum($col);
            }
        }

        return $data;
    }

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

        // ── Sumber 1: MoU approved (cost_list_mous) ──
        // JP DEBIT  AO-103.x = piutang per CoA dari MoU
        // JP KREDIT AO-208   = total MoU (Pendapatan Belum Diterima)
        $mouJP        = $this->getMouPiutangForJP($startOfCurrentMonth, $endOfCurrentMonth);
        $byPiutangCoa = $mouJP['by_piutang_coa']; // [piutang_coa_id => total] -> JP DEBIT AO-103.x
        $mouTotal     = $mouJP['total'];           // -> JP KREDIT AO-208

        // ── Sumber 2: cash_reports (CoA AO-103.x di kas/bank) ──
        // JP DEBIT  AO-208   = total kas diterima (pengurang accrual Pendapatan Belum Diterima)
        // JP KREDIT AO-401.x = nilai penerimaan per CoA (pengakuan pendapatan aktual)
        $cashJP          = $this->getCashReportPiutangForJP($startOfCurrentMonth, $endOfCurrentMonth);
        $byPendapatanCoa = $cashJP['by_pendapatan_coa']; // [pendapatan_coa_id => total] -> JP KREDIT AO-401.x
        $cashTotal       = $cashJP['total'];              // -> JP DEBIT AO-208

        $coaBelumDiterimaId = self::COA_PENDAPATAN_BELUM_DITERIMA_ID;

        // Build CASE expression untuk DEBIT Jurnal Pendapatan:
        //   AO-103.x -> nilai piutang per CoA dari MoU approved
        //   AO-208   -> total penerimaan kas (mengurangi accrual Pendapatan Belum Diterima)
        $debitCases = "CASE coa.id WHEN {$coaBelumDiterimaId} THEN {$cashTotal}";
        foreach ($byPiutangCoa as $coaId => $total) {
            $debitCases .= " WHEN {$coaId} THEN {$total}";
        }
        $debitCases .= ' ELSE 0 END';

        // Build CASE expression untuk KREDIT Jurnal Pendapatan:
        //   AO-208   -> total MoU (Pendapatan Belum Diterima, kredit accrual)
        //   AO-401.x -> nilai penerimaan dari cash_reports (pengakuan pendapatan aktual)
        $kreditCases = "CASE coa.id WHEN {$coaBelumDiterimaId} THEN {$mouTotal}";
        foreach ($byPendapatanCoa as $coaId => $total) {
            $kreditCases .= " WHEN {$coaId} THEN {$total}";
        }
        $kreditCases .= ' ELSE 0 END';

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
                DB::raw('COALESCE(aje_data.aje_debit, 0) as aje_debit'),
                DB::raw('COALESCE(aje_data.aje_kredit, 0) as aje_kredit'),
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
                'coa.id',
                '=',
                'journal_data.coa_id'
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
                'coa.id',
                '=',
                'kas_besar_data.coa_id'
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
                'coa.id',
                '=',
                'kas_kecil_data.coa_id'
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
                'coa.id',
                '=',
                'bank_data.coa_id'
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
                'coa.id',
                '=',
                'jurnal_umum_data.coa_id'
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
                'coa.id',
                '=',
                'aje_data.coa_id'
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
                'coa.id',
                '=',
                'neraca_awal_bulan_depan.coa_id'
            )
            ->where('coa.deleted_at', null)
            ->where('coa.type', 'kkp')
            ->orderBy('coa.group_coa_id')
            ->orderBy('coa.sort_order');

        return $query;
    }

    public function getTitle(): string
    {
        $monthName = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
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
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
        $periodeLabel = $monthNames[$this->month] . ' ' . $this->year;

        $headerStyle = [
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
        ];
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
        $sheetSum->mergeCells('A1:E1');
        $sheetSum->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheetSum->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheetSum->setCellValue('A3', 'Kode COA');
        $sheetSum->setCellValue('B3', 'Nama COA');
        $sheetSum->setCellValue('C3', 'Sumber');
        $sheetSum->setCellValue('D3', 'Nominal');
        $sheetSum->setCellValue('E3', 'JP Debit');
        $sheetSum->setCellValue('F3', 'JP Kredit');
        $sheetSum->mergeCells('A1:F1');
        $sheetSum->getStyle('A3:F3')->applyFromArray($headerStyle);

        $piutangMap        = $this->getPiutangToPendapatanMap();
        $piutangCoaIds     = array_keys($piutangMap);
        $pendapatanCoaIds  = array_values($piutangMap);
        $pendapatanCoaList = DB::table('coa')->whereIn('id', $pendapatanCoaIds)->get()->keyBy('id');
        $piutangCoaList    = DB::table('coa')->whereIn('id', $piutangCoaIds)->get()->keyBy('id');
        $coaBelumDiterima  = DB::table('coa')->where('id', self::COA_PENDAPATAN_BELUM_DITERIMA_ID)->first();

        // ── Bagian 1: MoU (DR AO-103.x / CR AO-208) ──
        $mouRows = DB::table('cost_list_mous as clm')
            ->join('mous as m', 'm.id', '=', 'clm.mou_id')
            ->whereNull('m.deleted_at')
            ->whereNull('clm.deleted_at')
            ->where('m.status', 'approved')
            ->where('m.type', 'kkp')
            ->whereBetween('m.approved_date', [$startOfMonth, $endOfMonth])
            ->whereIn('clm.coa_id', $piutangCoaIds)
            ->groupBy('clm.coa_id')
            ->selectRaw('clm.coa_id as piutang_coa_id, SUM(clm.total_amount) as total')
            ->get();

        $sumRow   = 4;
        $mouTotal = $mouRows->sum('total');

        // Label sub-header bagian 1
        $sheetSum->setCellValue('A' . $sumRow, '─── BAGIAN 1: MoU Approved (Pengakuan Piutang) ───');
        $sheetSum->mergeCells('A' . $sumRow . ':F' . $sumRow);
        $sheetSum->getStyle('A' . $sumRow . ':F' . $sumRow)->getFont()->setBold(true)->setItalic(true);
        $sumRow++;

        foreach ($mouRows as $row) {
            $piutangCoa = $piutangCoaList->get($row->piutang_coa_id);
            // DR AO-103.x
            $sheetSum->setCellValue('A' . $sumRow, $piutangCoa ? $piutangCoa->code : $row->piutang_coa_id);
            $sheetSum->setCellValue('B' . $sumRow, $piutangCoa ? $piutangCoa->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, 'MoU Approved');
            $sheetSum->setCellValue('D' . $sumRow, $row->total ?: '');
            $sheetSum->setCellValue('E' . $sumRow, $row->total ?: ''); // Debit
            $sheetSum->setCellValue('F' . $sumRow, '');
            $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $sumRow++;
        }
        // CR AO-208 = total MoU
        $sheetSum->setCellValue('A' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->code : 'AO-208');
        $sheetSum->setCellValue('B' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->name : 'Pendapatan Yang Belum Diterima');
        $sheetSum->setCellValue('C' . $sumRow, 'MoU Approved');
        $sheetSum->setCellValue('D' . $sumRow, $mouTotal ?: '');
        $sheetSum->setCellValue('E' . $sumRow, '');
        $sheetSum->setCellValue('F' . $sumRow, $mouTotal ?: ''); // Kredit
        $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sumRow++;

        // ── Bagian 2: Penerimaan Kas (CR AO-401.x) ──
        $cashRows = DB::table('cash_reports')
            ->whereNull('deleted_at')
            ->whereIn('coa_id', $piutangCoaIds)
            ->whereIn('cash_reference_id', [1, 2, 3, 4, 5, 6, 7])
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->groupBy('coa_id')
            ->selectRaw('coa_id as piutang_coa_id, SUM(debit_amount) as total')
            ->get();

        $cashTotal = $cashRows->sum('total');

        $sheetSum->setCellValue('A' . $sumRow, '─── BAGIAN 2: Penerimaan Kas (Pengakuan Pendapatan) ───');
        $sheetSum->mergeCells('A' . $sumRow . ':F' . $sumRow);
        $sheetSum->getStyle('A' . $sumRow . ':F' . $sumRow)->getFont()->setBold(true)->setItalic(true);
        $sumRow++;

        foreach ($cashRows as $row) {
            $pendapatanCoaId = $piutangMap[$row->piutang_coa_id] ?? null;
            $pendapatanCoa   = $pendapatanCoaId ? $pendapatanCoaList->get($pendapatanCoaId) : null;
            // CR AO-401.x
            $sheetSum->setCellValue('A' . $sumRow, $pendapatanCoa ? $pendapatanCoa->code : '-');
            $sheetSum->setCellValue('B' . $sumRow, $pendapatanCoa ? $pendapatanCoa->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, 'Penerimaan Kas');
            $sheetSum->setCellValue('D' . $sumRow, $row->total ?: '');
            $sheetSum->setCellValue('E' . $sumRow, '');
            $sheetSum->setCellValue('F' . $sumRow, $row->total ?: ''); // Kredit
            $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $sumRow++;
        }
        // DR AO-208 = total penerimaan kas (pengurang accrual Pendapatan Belum Diterima)
        $sheetSum->setCellValue('A' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->code : 'AO-208');
        $sheetSum->setCellValue('B' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->name : 'Pendapatan Yang Belum Diterima');
        $sheetSum->setCellValue('C' . $sumRow, 'Penerimaan Kas');
        $sheetSum->setCellValue('D' . $sumRow, $cashTotal ?: '');
        $sheetSum->setCellValue('E' . $sumRow, $cashTotal ?: ''); // Debit
        $sheetSum->setCellValue('F' . $sumRow, '');
        $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sumRow++;

        // Baris Grand Total
        // JP Debit total  = MoU (AO-103.x) + Kas (AO-208) = mouTotal + cashTotal
        // JP Kredit total = MoU (AO-208)   + Kas (AO-401) = mouTotal + cashTotal
        $jpDebitTotal  = $mouTotal + $cashTotal;
        $jpKreditTotal = $mouTotal + $cashTotal;
        $sheetSum->setCellValue('A' . $sumRow, 'TOTAL');
        $sheetSum->mergeCells('A' . $sumRow . ':C' . $sumRow);
        $sheetSum->setCellValue('D' . $sumRow, '');
        $sheetSum->setCellValue('E' . $sumRow, $jpDebitTotal ?: '');
        $sheetSum->setCellValue('F' . $sumRow, $jpKreditTotal ?: '');
        $sheetSum->getStyle('A' . $sumRow . ':F' . $sumRow)->applyFromArray($totalStyle);
        $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);

        $sheetSum->getStyle('A3:F' . $sumRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        foreach (range('A', 'F') as $col) {
            $sheetSum->getColumnDimension($col)->setAutoSize(true);
        }

        // ──────────────────────────────────────────────────────────────────────
        // SHEET 2: DETAIL TRANSAKSI PIUTANG DI KAS/BANK
        // ──────────────────────────────────────────────────────────────────────
        $sheetDetail = $spreadsheet->createSheet();
        $sheetDetail->setTitle('Detail Piutang Kas Bank');

        $sheetDetail->setCellValue('A1', 'DETAIL PIUTANG (AO-103.x) DI KAS/BANK - ' . strtoupper($periodeLabel));
        $sheetDetail->mergeCells('A1:I1');
        $sheetDetail->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheetDetail->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $detail_headers = [
            'A3' => 'Tgl Transaksi',
            'B3' => 'Referensi Kas',
            'C3' => 'Kode COA Piutang',
            'D3' => 'Nama COA Piutang',
            'E3' => 'Kode COA Pendapatan',
            'F3' => 'Nama COA Pendapatan',
            'G3' => 'Debit',
            'H3' => 'Kredit',
            'I3' => 'Keterangan',
        ];
        foreach ($detail_headers as $cell => $label) {
            $sheetDetail->setCellValue($cell, $label);
        }
        $sheetDetail->getStyle('A3:I3')->applyFromArray($headerStyle);

        $detailRows = DB::table('cash_reports as cr')
            ->join('coa as c_piutang', 'c_piutang.id', '=', 'cr.coa_id')
            ->join('cash_references as cref', 'cref.id', '=', 'cr.cash_reference_id')
            ->whereNull('cr.deleted_at')
            ->whereIn('cr.coa_id', $piutangCoaIds)
            ->whereIn('cr.cash_reference_id', [1, 2, 3, 4, 5, 6, 7])
            ->whereBetween('cr.transaction_date', [$startOfMonth, $endOfMonth])
            ->orderBy('cr.transaction_date')
            ->orderBy('cr.id')
            ->selectRaw('
                cr.transaction_date,
                cref.name as ref_name,
                c_piutang.code as piutang_code,
                c_piutang.name as piutang_name,
                cr.coa_id as piutang_coa_id,
                cr.debit_amount,
                cr.credit_amount,
                cr.description
            ')
            ->get();

        $detailRow   = 4;
        $detailGrand = 0;
        foreach ($detailRows as $dr) {
            $pendapatanCoaId = $piutangMap[$dr->piutang_coa_id] ?? null;
            $pendapatanCoa   = $pendapatanCoaId ? $pendapatanCoaList->get($pendapatanCoaId) : null;

            $sheetDetail->setCellValue('A' . $detailRow, $dr->transaction_date);
            $sheetDetail->setCellValue('B' . $detailRow, $dr->ref_name);
            $sheetDetail->setCellValue('C' . $detailRow, $dr->piutang_code);
            $sheetDetail->setCellValue('D' . $detailRow, $dr->piutang_name);
            $sheetDetail->setCellValue('E' . $detailRow, $pendapatanCoa ? $pendapatanCoa->code : '-');
            $sheetDetail->setCellValue('F' . $detailRow, $pendapatanCoa ? $pendapatanCoa->name : '-');
            $sheetDetail->setCellValue('G' . $detailRow, $dr->debit_amount ?: '');
            $sheetDetail->setCellValue('H' . $detailRow, $dr->credit_amount ?: '');
            $sheetDetail->setCellValue('I' . $detailRow, $dr->description);
            $sheetDetail->getStyle('G' . $detailRow . ':H' . $detailRow)->getNumberFormat()->setFormatCode($numberFmt);
            $detailGrand += $dr->debit_amount;
            $detailRow++;
        }
        $sheetDetail->setCellValue('F' . $detailRow, 'TOTAL');
        $sheetDetail->setCellValue('G' . $detailRow, $detailGrand);
        $sheetDetail->getStyle('A' . $detailRow . ':I' . $detailRow)->applyFromArray($totalStyle);
        $sheetDetail->getStyle('G' . $detailRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sheetDetail->getStyle('A3:I' . $detailRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
        foreach (range('A', 'I') as $col) {
            $sheetDetail->getColumnDimension($col)->setAutoSize(true);
        }

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
        $headers = [
            'B3' => 'Neraca Awal',
            'D3' => 'Kas Besar',
            'F3' => 'Kas Kecil',
            'H3' => 'Bank',
            'J3' => 'Jurnal Pendapatan (Piutang)',
            'L3' => 'Jurnal Umum',
            'N3' => 'Neraca Sebelum AJE',
            'P3' => 'AJE',
            'R3' => 'Neraca Setelah AJE',
            'T3' => 'Neraca',
            'V3' => 'Laba Rugi'
        ];
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

        $data = $this->getTableData();
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
