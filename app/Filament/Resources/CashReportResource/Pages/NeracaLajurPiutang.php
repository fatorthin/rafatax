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
            ->whereIn('cash_reference_id', [1, 2, 3, 4, 5, 6, 7, 9]) // bank + kas besar + kas kecil
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

    /**
     * Sumber 3 — invoices (is_include_pph23 = true, dibuat pada bulan berjalan).
     *
     * Entri JP yang dihasilkan:
     *   JP DEBIT  : AO-103.x (piutang, disesuaikan) = nilai PPh23 per CoA
     *   JP KREDIT : AO-208 (Pendapatan Belum Diterima) = total PPh23
     *
     * Return:
     *   - by_piutang_coa : [ piutang_coa_id => total ]  -> JP DEBIT
     *   - total          : grand total                   -> JP KREDIT AO-208
     */
    private function getInvoicePph23ForJP(string $startOfMonth, string $endOfMonth): array
    {
        $rows = DB::table('invoices as inv')
            ->join('cost_list_invoices as cli', 'cli.invoice_id', '=', 'inv.id')
            ->whereNull('inv.deleted_at')
            ->whereNull('cli.deleted_at')
            ->where('inv.is_include_pph23', true)
            ->whereBetween('inv.invoice_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('cli.coa_id, cli.amount')
            ->get();

        $byPiutangCoa = [];
        $grandTotal   = 0;

        $revenueToPiutangMap = [
            119 => 188, // Fee Bulanan
            120 => 182, // Fee SPT
            121 => 183, // Fee SP2DK
            122 => 184, // Fee Pembetulan
            123 => 185, // Fee Internal
            124 => 186, // Fee Restitusi
            125 => 187, // Fee Pemeriksaan
        ];

        foreach ($rows as $row) {
            $coaId = $row->coa_id;

            // Map revenue CoA to piutang CoA
            if (isset($revenueToPiutangMap[$coaId])) {
                $coaId = $revenueToPiutangMap[$coaId];
            }

            // Override Bulanan (188) and Tahunan/SPT (182) to Bulanan (188 / AO-103.6)
            if ($coaId == 188 || $coaId == 182) {
                $coaId = 188;
            }

            $pph23Amount = ($row->amount / 98) * 2;

            $byPiutangCoa[$coaId] = ($byPiutangCoa[$coaId] ?? 0) + $pph23Amount;
            $grandTotal          += $pph23Amount;
        }

        return [
            'by_piutang_coa' => $byPiutangCoa,
            'total'          => $grandTotal,
        ];
    }

    /**
     * Sumber 4 — invoices (is_pph23_checked = true, dibuat pada bulan berjalan).
     *
     * Entri JP yang dihasilkan:
     *   JP DEBIT  : AO-208 (Pendapatan Belum Diterima) = total PPh23
     *   JP DEBIT  : AO-518.1 (Biaya PPH 23)           = total PPh23
     *   JP KREDIT : AO-401.x (Pendapatan, disesuaikan) = nilai per CoA
     *   JP KREDIT : AO-103.x (Piutang, disesuaikan)   = nilai per CoA
     */
    private function getInvoicePphCheckedForJP(string $startOfMonth, string $endOfMonth): array
    {
        $rows = DB::table('invoices as inv')
            ->join('cost_list_invoices as cli', 'cli.invoice_id', '=', 'inv.id')
            ->whereNull('inv.deleted_at')
            ->whereNull('cli.deleted_at')
            ->where('inv.is_pph23_checked', true)
            ->whereBetween('inv.tanggal_bukti_potong_pph23', [$startOfMonth, $endOfMonth])
            ->selectRaw('inv.id as invoice_id, cli.coa_id, cli.amount as item_amount, inv.nominal_bukti_potong_pph23')
            ->get();

        $invoiceTotals = [];
        foreach ($rows as $row) {
            $invoiceTotals[$row->invoice_id] = ($invoiceTotals[$row->invoice_id] ?? 0) + $row->item_amount;
        }

        $byPiutangCoa = [];
        $byPendapatanCoa = [];
        $grandTotal   = 0;

        $revenueToPiutangMap = [
            119 => 188, // Fee Bulanan
            120 => 182, // Fee SPT
            121 => 183, // Fee SP2DK
            122 => 184, // Fee Pembetulan
            123 => 185, // Fee Internal
            124 => 186, // Fee Restitusi
            125 => 187, // Fee Pemeriksaan
        ];

        $piutangToPendapatanMap = [
            188 => 119, // AO-103.6  -> AO-401   (Fee Bulanan)
            182 => 120, // AO-103.7  -> AO-401.1 (Fee SPT)
            183 => 121, // AO-103.8  -> AO-401.2 (Fee SP2DK)
            184 => 122, // AO-103.9  -> AO-401.3 (Fee Pembetulan)
            185 => 123, // AO-103.10 -> AO-401.4 (Fee Internal)
            186 => 124, // AO-103.11 -> AO-401.5 (Fee Restitusi)
            187 => 125, // AO-103.12 -> AO-401.6 (Fee Pemeriksaan)
        ];

        foreach ($rows as $row) {
            $coaId = $row->coa_id;

            if (isset($revenueToPiutangMap[$coaId])) {
                $pendapatanCoaId = $coaId;
                $piutangCoaId = $revenueToPiutangMap[$coaId];
            } elseif (isset($piutangToPendapatanMap[$coaId])) {
                $piutangCoaId = $coaId;
                $pendapatanCoaId = $piutangToPendapatanMap[$coaId];
            } else {
                $piutangCoaId = $coaId;
                $pendapatanCoaId = $coaId;
            }

            // Override Bulanan/Tahunan to Bulanan (AO-103.6 / AO-401)
            if ($piutangCoaId == 188 || $piutangCoaId == 182) {
                $piutangCoaId = 188;
                $pendapatanCoaId = 119;
            }

            $invoiceTotal = $invoiceTotals[$row->invoice_id] ?? 0;
            $pph23Amount = ($invoiceTotal > 0) ? ($row->item_amount / $invoiceTotal) * $row->nominal_bukti_potong_pph23 : 0;

            $byPiutangCoa[$piutangCoaId] = ($byPiutangCoa[$piutangCoaId] ?? 0) + $pph23Amount;
            $byPendapatanCoa[$pendapatanCoaId] = ($byPendapatanCoa[$pendapatanCoaId] ?? 0) + $pph23Amount;
            $grandTotal += $pph23Amount;
        }

        return [
            'by_piutang_coa' => $byPiutangCoa,
            'by_pendapatan_coa' => $byPendapatanCoa,
            'total'          => $grandTotal,
        ];
    }

    /**
     * Sumber 5 — MoU discounts (discount_amount > 0 && tgl_discount jatuh pada bulan berjalan).
     */
    private function getMouDiscountForJP(string $startOfMonth, string $endOfMonth): array
    {
        $mous = DB::table('mous as m')
            ->whereNull('m.deleted_at')
            ->where('m.status', 'approved')
            ->where('m.discount_amount', '>', 0)
            ->whereNotNull('m.tgl_discount')
            ->whereBetween('m.tgl_discount', [$startOfMonth, $endOfMonth])
            ->select([
                'm.id',
                'm.discount_amount',
                'm.category_mou_id'
            ])
            ->get();

        $map = $this->getPiutangToPendapatanMap();

        $debits = [];
        $kredits = [];

        foreach ($mous as $mou) {
            $clCoa = DB::table('cost_list_mous')
                ->where('mou_id', $mou->id)
                ->whereNull('deleted_at')
                ->whereIn('coa_id', array_keys($map))
                ->value('coa_id');

            if ($clCoa) {
                $piutangCoaId = $clCoa;
            } else {
                $piutangCoaId = match ($mou->category_mou_id) {
                    1, 2 => 182,
                    3, 4 => 188,
                    5 => 183,
                    6 => 184,
                    7 => 187,
                    8 => 186,
                    default => 188,
                };
            }

            $pendapatanCoaId = $map[$piutangCoaId] ?? 119;
            $discountAmount = (float) $mou->discount_amount;

            // Debits:
            // 1. AO-420 (Potongan Pendapatan: 190)
            $debits[190] = ($debits[190] ?? 0) + $discountAmount;
            // 2. AO-208 (Pendapatan Yang Belum Diterima: 175)
            $debits[175] = ($debits[175] ?? 0) + $discountAmount;

            // Kredits:
            // 1. AO-103.x (Piutang: $piutangCoaId)
            $kredits[$piutangCoaId] = ($kredits[$piutangCoaId] ?? 0) + $discountAmount;
            // 2. AO-401.x (Pendapatan: $pendapatanCoaId)
            $kredits[$pendapatanCoaId] = ($kredits[$pendapatanCoaId] ?? 0) + $discountAmount;
        }

        return [
            'debits' => $debits,
            'kredits' => $kredits
        ];
    }

    /**
     * Sumber 6 — MoU cancellations (cancel_mou_amount > 0 && tgl_cancel_mou jatuh pada bulan berjalan).
     */
    private function getMouCancellationForJP(string $startOfMonth, string $endOfMonth): array
    {
        $mous = DB::table('mous as m')
            ->whereNull('m.deleted_at')
            ->where('m.status', 'approved')
            ->where('m.cancel_mou_amount', '>', 0)
            ->whereNotNull('m.tgl_cancel_mou')
            ->whereBetween('m.tgl_cancel_mou', [$startOfMonth, $endOfMonth])
            ->select([
                'm.id',
                'm.cancel_mou_amount',
                'm.category_mou_id'
            ])
            ->get();

        $map = $this->getPiutangToPendapatanMap();

        $debits = [];
        $kredits = [];

        foreach ($mous as $mou) {
            $clCoa = DB::table('cost_list_mous')
                ->where('mou_id', $mou->id)
                ->whereNull('deleted_at')
                ->whereIn('coa_id', array_keys($map))
                ->value('coa_id');

            if ($clCoa) {
                $piutangCoaId = $clCoa;
            } else {
                $piutangCoaId = match ($mou->category_mou_id) {
                    1, 2 => 182,
                    3, 4 => 188,
                    5 => 183,
                    6 => 184,
                    7 => 187,
                    8 => 186,
                    default => 188,
                };
            }

            $cancelAmount = (float) $mou->cancel_mou_amount;

            // Debits:
            // 1. AO-208 (Pendapatan Yang Belum Diterima: 175)
            $debits[175] = ($debits[175] ?? 0) + $cancelAmount;

            // Kredits:
            // 1. AO-103.x (Piutang: $piutangCoaId)
            $kredits[$piutangCoaId] = ($kredits[$piutangCoaId] ?? 0) + $cancelAmount;
        }

        return [
            'debits' => $debits,
            'kredits' => $kredits
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
                'kas_besar_debit',
                'kas_besar_kredit',
                'kas_kecil_debit',
                'kas_kecil_kredit',
                'bank_debit',
                'bank_kredit',
                'jurnal_pendapatan_debit',
                'jurnal_pendapatan_kredit',
                'jurnal_umum_debit',
                'jurnal_umum_kredit',
                'aje_debit',
                'aje_kredit',
                'neraca_awal_bulan_depan_debit',
                'neraca_awal_bulan_depan_kredit'
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

        $jpDebits = [];
        $jpKredits = [];

        $coaBelumDiterimaId = self::COA_PENDAPATAN_BELUM_DITERIMA_ID;
        $biayaPph23Id       = 91; // AO-108.1 PPh 23

        // ── Sumber 1: MoU approved (cost_list_mous) ──
        // JP DEBIT  AO-103.x = piutang per CoA dari MoU
        // JP KREDIT AO-208   = total MoU (Pendapatan Belum Diterima)
        $mouJP = $this->getMouPiutangForJP($startOfCurrentMonth, $endOfCurrentMonth);
        foreach ($mouJP['by_piutang_coa'] as $coaId => $total) {
            $jpDebits[$coaId] = ($jpDebits[$coaId] ?? 0) + $total;
        }
        $jpKredits[$coaBelumDiterimaId] = ($jpKredits[$coaBelumDiterimaId] ?? 0) + $mouJP['total'];

        // ── Sumber 2: cash_reports (CoA AO-103.x di kas/bank) ──
        // JP DEBIT  AO-208   = total kas diterima (pengurang accrual Pendapatan Belum Diterima)
        // JP KREDIT AO-401.x = nilai penerimaan per CoA (pengakuan pendapatan aktual)
        $cashJP = $this->getCashReportPiutangForJP($startOfCurrentMonth, $endOfCurrentMonth);
        foreach ($cashJP['by_pendapatan_coa'] as $coaId => $total) {
            $jpKredits[$coaId] = ($jpKredits[$coaId] ?? 0) + $total;
        }
        $jpDebits[$coaBelumDiterimaId] = ($jpDebits[$coaBelumDiterimaId] ?? 0) + $cashJP['total'];

        // ── Sumber 3: PPh23 dari Invoice yang di-checklist is_include_pph23 ──
        // JP DEBIT  AO-103.x = nilai PPh23 per CoA
        // JP KREDIT AO-208   = total PPh23
        $pph23JP = $this->getInvoicePph23ForJP($startOfCurrentMonth, $endOfCurrentMonth);
        foreach ($pph23JP['by_piutang_coa'] as $coaId => $total) {
            $jpDebits[$coaId] = ($jpDebits[$coaId] ?? 0) + $total;
        }
        $jpKredits[$coaBelumDiterimaId] = ($jpKredits[$coaBelumDiterimaId] ?? 0) + $pph23JP['total'];

        // ── Sumber 4: PPh23 dari Invoice yang di-checklist is_pph23_checked ──
        // JP DEBIT  AO-208   = total PPh23
        // JP DEBIT  AO-518.1 = total PPh23 (Biaya PPH 23)
        // JP KREDIT AO-401.x = nilai PPh23 per Pendapatan CoA
        // JP KREDIT AO-103.x = nilai PPh23 per Piutang CoA
        $pphCheckedJP = $this->getInvoicePphCheckedForJP($startOfCurrentMonth, $endOfCurrentMonth);
        $jpDebits[$coaBelumDiterimaId] = ($jpDebits[$coaBelumDiterimaId] ?? 0) + $pphCheckedJP['total'];
        $jpDebits[$biayaPph23Id]       = ($jpDebits[$biayaPph23Id] ?? 0) + $pphCheckedJP['total'];
        foreach ($pphCheckedJP['by_pendapatan_coa'] as $coaId => $total) {
            $jpKredits[$coaId] = ($jpKredits[$coaId] ?? 0) + $total;
        }
        foreach ($pphCheckedJP['by_piutang_coa'] as $coaId => $total) {
            $jpKredits[$coaId] = ($jpKredits[$coaId] ?? 0) + $total;
        }

        // ── Sumber 5: Diskon dari MoU ──
        $mouDiscountJP = $this->getMouDiscountForJP($startOfCurrentMonth, $endOfCurrentMonth);
        foreach ($mouDiscountJP['debits'] as $coaId => $total) {
            $jpDebits[$coaId] = ($jpDebits[$coaId] ?? 0) + $total;
        }
        foreach ($mouDiscountJP['kredits'] as $coaId => $total) {
            $jpKredits[$coaId] = ($jpKredits[$coaId] ?? 0) + $total;
        }

        // ── Sumber 6: Cancel dari MoU ──
        $mouCancelJP = $this->getMouCancellationForJP($startOfCurrentMonth, $endOfCurrentMonth);
        foreach ($mouCancelJP['debits'] as $coaId => $total) {
            $jpDebits[$coaId] = ($jpDebits[$coaId] ?? 0) + $total;
        }
        foreach ($mouCancelJP['kredits'] as $coaId => $total) {
            $jpKredits[$coaId] = ($jpKredits[$coaId] ?? 0) + $total;
        }

        // Build CASE expression untuk DEBIT Jurnal Pendapatan:
        $debitCases = "CASE coa.id";
        if (empty($jpDebits)) {
            $debitCases .= " WHEN 0 THEN 0";
        } else {
            foreach ($jpDebits as $coaId => $total) {
                $debitCases .= " WHEN {$coaId} THEN {$total}";
            }
        }
        $debitCases .= ' ELSE 0 END';

        // Build CASE expression untuk KREDIT Jurnal Pendapatan:
        $kreditCases = "CASE coa.id";
        if (empty($jpKredits)) {
            $kreditCases .= " WHEN 0 THEN 0";
        } else {
            foreach ($jpKredits as $coaId => $total) {
                $kreditCases .= " WHEN {$coaId} THEN {$total}";
            }
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
                        WHERE cash_reference_id IN (7, 9)
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
                        WHERE cash_reference_id IN (7, 9)
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
            ->whereIn('cash_reference_id', [1, 2, 3, 4, 5, 6, 7, 9])
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

        // ── Bagian 3: PPh23 dari Invoice (DR AO-103.x / CR AO-208) ──
        $pph23JP      = $this->getInvoicePph23ForJP($startOfMonth, $endOfMonth);
        $byPph23Coa   = $pph23JP['by_piutang_coa'];
        $pph23Total   = $pph23JP['total'];

        $sheetSum->setCellValue('A' . $sumRow, '─── BAGIAN 3: PPh23 Invoice (Pengakuan PPh23) ───');
        $sheetSum->mergeCells('A' . $sumRow . ':F' . $sumRow);
        $sheetSum->getStyle('A' . $sumRow . ':F' . $sumRow)->getFont()->setBold(true)->setItalic(true);
        $sumRow++;

        foreach ($byPph23Coa as $coaId => $total) {
            $piutangCoa = $piutangCoaList->get($coaId) ?? DB::table('coa')->where('id', $coaId)->first();
            // DR AO-103.x
            $sheetSum->setCellValue('A' . $sumRow, $piutangCoa ? $piutangCoa->code : $coaId);
            $sheetSum->setCellValue('B' . $sumRow, $piutangCoa ? $piutangCoa->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, 'PPh23 Invoice');
            $sheetSum->setCellValue('D' . $sumRow, $total ?: '');
            $sheetSum->setCellValue('E' . $sumRow, $total ?: ''); // Debit
            $sheetSum->setCellValue('F' . $sumRow, '');
            $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $sumRow++;
        }
        // CR AO-208 = total PPh23
        $sheetSum->setCellValue('A' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->code : 'AO-208');
        $sheetSum->setCellValue('B' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->name : 'Pendapatan Yang Belum Diterima');
        $sheetSum->setCellValue('C' . $sumRow, 'PPh23 Invoice');
        $sheetSum->setCellValue('D' . $sumRow, $pph23Total ?: '');
        $sheetSum->setCellValue('E' . $sumRow, '');
        $sheetSum->setCellValue('F' . $sumRow, $pph23Total ?: ''); // Kredit
        $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sumRow++;

        // ── Bagian 4: PPh23 dari Invoice yang di-checklist (DR AO-208 & DR AO-518.1 / CR AO-401.x & CR AO-103.x) ──
        $pphCheckedJP   = $this->getInvoicePphCheckedForJP($startOfMonth, $endOfMonth);
        $byPphCheckedPiutang = $pphCheckedJP['by_piutang_coa'];
        $byPphCheckedPendapatan = $pphCheckedJP['by_pendapatan_coa'];
        $pphCheckedTotal = $pphCheckedJP['total'];

        $sheetSum->setCellValue('A' . $sumRow, '─── BAGIAN 4: PPh23 Checklist (Biaya PPh23) ───');
        $sheetSum->mergeCells('A' . $sumRow . ':F' . $sumRow);
        $sheetSum->getStyle('A' . $sumRow . ':F' . $sumRow)->getFont()->setBold(true)->setItalic(true);
        $sumRow++;

        // DR AO-208
        $sheetSum->setCellValue('A' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->code : 'AO-208');
        $sheetSum->setCellValue('B' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->name : 'Pendapatan Yang Belum Diterima');
        $sheetSum->setCellValue('C' . $sumRow, 'PPh23 Checklist');
        $sheetSum->setCellValue('D' . $sumRow, $pphCheckedTotal ?: '');
        $sheetSum->setCellValue('E' . $sumRow, $pphCheckedTotal ?: ''); // Debit
        $sheetSum->setCellValue('F' . $sumRow, '');
        $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sumRow++;

        // DR AO-108.1
        $coaBiayaPph = DB::table('coa')->where('id', 91)->first();
        $sheetSum->setCellValue('A' . $sumRow, $coaBiayaPph ? $coaBiayaPph->code : 'AO-108.1');
        $sheetSum->setCellValue('B' . $sumRow, $coaBiayaPph ? $coaBiayaPph->name : 'PPh 23');
        $sheetSum->setCellValue('C' . $sumRow, 'PPh23 Checklist');
        $sheetSum->setCellValue('D' . $sumRow, $pphCheckedTotal ?: '');
        $sheetSum->setCellValue('E' . $sumRow, $pphCheckedTotal ?: ''); // Debit
        $sheetSum->setCellValue('F' . $sumRow, '');
        $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sumRow++;

        // CR AO-401.x
        foreach ($byPphCheckedPendapatan as $coaId => $total) {
            $pCoa = DB::table('coa')->where('id', $coaId)->first();
            $sheetSum->setCellValue('A' . $sumRow, $pCoa ? $pCoa->code : $coaId);
            $sheetSum->setCellValue('B' . $sumRow, $pCoa ? $pCoa->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, 'PPh23 Checklist');
            $sheetSum->setCellValue('D' . $sumRow, $total ?: '');
            $sheetSum->setCellValue('E' . $sumRow, '');
            $sheetSum->setCellValue('F' . $sumRow, $total ?: ''); // Kredit
            $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $sumRow++;
        }

        // CR AO-103.x
        foreach ($byPphCheckedPiutang as $coaId => $total) {
            $pCoa = $piutangCoaList->get($coaId) ?? DB::table('coa')->where('id', $coaId)->first();
            $sheetSum->setCellValue('A' . $sumRow, $pCoa ? $pCoa->code : $coaId);
            $sheetSum->setCellValue('B' . $sumRow, $pCoa ? $pCoa->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, 'PPh23 Checklist');
            $sheetSum->setCellValue('D' . $sumRow, $total ?: '');
            $sheetSum->setCellValue('E' . $sumRow, '');
            $sheetSum->setCellValue('F' . $sumRow, $total ?: ''); // Kredit
            $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $sumRow++;
        }

        // ── Bagian 5: MoU Discounts (DR AO-420 & DR AO-208 / CR AO-103.x & CR AO-401.x) ──
        $mouDiscountJP = $this->getMouDiscountForJP($startOfMonth, $endOfMonth);
        $discountDebits = $mouDiscountJP['debits'];
        $discountKredits = $mouDiscountJP['kredits'];

        $sheetSum->setCellValue('A' . $sumRow, '─── BAGIAN 5: MoU Discounts (Diskon MoU) ───');
        $sheetSum->mergeCells('A' . $sumRow . ':F' . $sumRow);
        $sheetSum->getStyle('A' . $sumRow . ':F' . $sumRow)->getFont()->setBold(true)->setItalic(true);
        $sumRow++;

        $discountTotalDebits = 0;
        foreach ($discountDebits as $coaId => $total) {
            $coaRecord = DB::table('coa')->where('id', $coaId)->first();
            $sheetSum->setCellValue('A' . $sumRow, $coaRecord ? $coaRecord->code : $coaId);
            $sheetSum->setCellValue('B' . $sumRow, $coaRecord ? $coaRecord->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, 'MoU Discount');
            $sheetSum->setCellValue('D' . $sumRow, $total ?: '');
            $sheetSum->setCellValue('E' . $sumRow, $total ?: ''); // Debit
            $sheetSum->setCellValue('F' . $sumRow, '');
            $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $discountTotalDebits += $total;
            $sumRow++;
        }

        $discountTotalKredits = 0;
        foreach ($discountKredits as $coaId => $total) {
            $coaRecord = DB::table('coa')->where('id', $coaId)->first();
            $sheetSum->setCellValue('A' . $sumRow, $coaRecord ? $coaRecord->code : $coaId);
            $sheetSum->setCellValue('B' . $sumRow, $coaRecord ? $coaRecord->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, 'MoU Discount');
            $sheetSum->setCellValue('D' . $sumRow, $total ?: '');
            $sheetSum->setCellValue('E' . $sumRow, '');
            $sheetSum->setCellValue('F' . $sumRow, $total ?: ''); // Kredit
            $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $discountTotalKredits += $total;
            $sumRow++;
        }

        // ── Bagian 6: MoU Cancellations (DR AO-208 / CR AO-103.x) ──
        $mouCancelJP = $this->getMouCancellationForJP($startOfMonth, $endOfMonth);
        $cancelDebits = $mouCancelJP['debits'];
        $cancelKredits = $mouCancelJP['kredits'];

        $sheetSum->setCellValue('A' . $sumRow, '─── BAGIAN 6: MoU Cancellations (Pembatalan MoU) ───');
        $sheetSum->mergeCells('A' . $sumRow . ':F' . $sumRow);
        $sheetSum->getStyle('A' . $sumRow . ':F' . $sumRow)->getFont()->setBold(true)->setItalic(true);
        $sumRow++;

        $cancelTotalDebits = 0;
        foreach ($cancelDebits as $coaId => $total) {
            $coaRecord = DB::table('coa')->where('id', $coaId)->first();
            $sheetSum->setCellValue('A' . $sumRow, $coaRecord ? $coaRecord->code : $coaId);
            $sheetSum->setCellValue('B' . $sumRow, $coaRecord ? $coaRecord->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, 'MoU Cancellation');
            $sheetSum->setCellValue('D' . $sumRow, $total ?: '');
            $sheetSum->setCellValue('E' . $sumRow, $total ?: ''); // Debit
            $sheetSum->setCellValue('F' . $sumRow, '');
            $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $cancelTotalDebits += $total;
            $sumRow++;
        }

        $cancelTotalKredits = 0;
        foreach ($cancelKredits as $coaId => $total) {
            $coaRecord = DB::table('coa')->where('id', $coaId)->first();
            $sheetSum->setCellValue('A' . $sumRow, $coaRecord ? $coaRecord->code : $coaId);
            $sheetSum->setCellValue('B' . $sumRow, $coaRecord ? $coaRecord->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, 'MoU Cancellation');
            $sheetSum->setCellValue('D' . $sumRow, $total ?: '');
            $sheetSum->setCellValue('E' . $sumRow, '');
            $sheetSum->setCellValue('F' . $sumRow, $total ?: ''); // Kredit
            $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $cancelTotalKredits += $total;
            $sumRow++;
        }

        // Baris Grand Total
        $jpDebitTotal  = $mouTotal + $cashTotal + $pph23Total + ($pphCheckedTotal * 2) + $discountTotalDebits + $cancelTotalDebits;
        $jpKreditTotal = $mouTotal + $cashTotal + $pph23Total + ($pphCheckedTotal * 2) + $discountTotalKredits + $cancelTotalKredits;
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
            ->whereIn('cr.cash_reference_id', [1, 2, 3, 4, 5, 6, 7, 9])
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

        // ──────────────────────────────────────────────────────────────────────
        // SHEET 3: DETAIL PPh23 INVOICE
        // ──────────────────────────────────────────────────────────────────────
        $sheetPph = $spreadsheet->createSheet();
        $sheetPph->setTitle('Detail PPh23 Invoice');

        $sheetPph->setCellValue('A1', 'DETAIL PPh23 INVOICE - ' . strtoupper($periodeLabel));
        $sheetPph->mergeCells('A1:G1');
        $sheetPph->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheetPph->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $pph_headers = [
            'A3' => 'No Invoice',
            'B3' => 'Tanggal Invoice',
            'C3' => 'Nama Klien',
            'D3' => 'Deskripsi',
            'E3' => 'Nilai Invoice',
            'F3' => 'Nominal PPh23',
            'G3' => 'CoA Terpetakan',
        ];
        foreach ($pph_headers as $cell => $label) {
            $sheetPph->setCellValue($cell, $label);
        }
        $sheetPph->getStyle('A3:G3')->applyFromArray($headerStyle);

        // Fetch invoice items with their mapped CoA
        $invoiceItems = DB::table('invoices as inv')
            ->join('cost_list_invoices as cli', 'cli.invoice_id', '=', 'inv.id')
            ->leftJoin('mous as m', 'm.id', '=', 'inv.mou_id')
            ->leftJoin('clients as c_inv', 'c_inv.id', '=', 'inv.client_id')
            ->leftJoin('clients as c_mou', 'c_mou.id', '=', 'm.client_id')
            ->leftJoin('memos as mem', 'mem.id', '=', 'inv.memo_id')
            ->whereNull('inv.deleted_at')
            ->whereNull('cli.deleted_at')
            ->where('inv.is_include_pph23', true)
            ->whereBetween('inv.invoice_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('
                inv.invoice_number,
                inv.invoice_date,
                inv.client_id,
                inv.mou_id,
                inv.memo_id,
                cli.description,
                cli.amount,
                cli.coa_id,
                c_inv.company_name as client_name_inv,
                c_mou.company_name as client_name_mou,
                mem.nama_klien as memo_nama,
                mem.instansi_klien as memo_instansi
            ')
            ->orderBy('inv.invoice_date')
            ->get();

        $pphRow = 4;
        $pphGrand = 0;

        $revenueToPiutangMap = [
            119 => 188, // Fee Bulanan
            120 => 182, // Fee SPT
            121 => 183, // Fee SP2DK
            122 => 184, // Fee Pembetulan
            123 => 185, // Fee Internal
            124 => 186, // Fee Restitusi
            125 => 187, // Fee Pemeriksaan
        ];

        foreach ($invoiceItems as $item) {
            // Determine client name
            $cName = '';
            if ($item->client_name_inv) {
                $cName = $item->client_name_inv;
            } elseif ($item->client_name_mou) {
                $cName = $item->client_name_mou;
            } elseif ($item->memo_nama || $item->memo_instansi) {
                $cName = $item->memo_nama ?? $item->memo_instansi ?? '';
            }

            // Determine coa mapping
            $mappedCoaId = $item->coa_id;
            if (isset($revenueToPiutangMap[$mappedCoaId])) {
                $mappedCoaId = $revenueToPiutangMap[$mappedCoaId];
            }
            if ($mappedCoaId == 188 || $mappedCoaId == 182) {
                $mappedCoaId = 188;
            }

            $coaRecord = $piutangCoaList->get($mappedCoaId) ?? DB::table('coa')->where('id', $mappedCoaId)->first();
            $coaLabel = $coaRecord ? ($coaRecord->code . ' - ' . $coaRecord->name) : $mappedCoaId;

            $pphAmount = ($item->amount / 98) * 2;

            $sheetPph->setCellValue('A' . $pphRow, $item->invoice_number);
            $sheetPph->setCellValue('B' . $pphRow, $item->invoice_date);
            $sheetPph->setCellValue('C' . $pphRow, $cName);
            $sheetPph->setCellValue('D' . $pphRow, $item->description);
            $sheetPph->setCellValue('E' . $pphRow, $item->amount ?: '');
            $sheetPph->setCellValue('F' . $pphRow, $pphAmount ?: '');
            $sheetPph->setCellValue('G' . $pphRow, $coaLabel);

            $sheetPph->getStyle('E' . $pphRow . ':F' . $pphRow)->getNumberFormat()->setFormatCode($numberFmt);
            $pphGrand += $pphAmount;
            $pphRow++;
        }

        $sheetPph->setCellValue('D' . $pphRow, 'TOTAL');
        $sheetPph->setCellValue('F' . $pphRow, $pphGrand);
        $sheetPph->getStyle('A' . $pphRow . ':G' . $pphRow)->applyFromArray($totalStyle);
        $sheetPph->getStyle('F' . $pphRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sheetPph->getStyle('A3:G' . $pphRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

        foreach (range('A', 'G') as $col) {
            $sheetPph->getColumnDimension($col)->setAutoSize(true);
        }

        // ──────────────────────────────────────────────────────────────────────
        // SHEET 4: DETAIL PPh23 CHECKLIST
        // ──────────────────────────────────────────────────────────────────────
        $sheetPphChecked = $spreadsheet->createSheet();
        $sheetPphChecked->setTitle('Detail PPh23 Checked');

        $sheetPphChecked->setCellValue('A1', 'DETAIL PPh23 CHECKLIST - ' . strtoupper($periodeLabel));
        $sheetPphChecked->mergeCells('A1:H1');
        $sheetPphChecked->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheetPphChecked->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $pph_checked_headers = [
            'A3' => 'No Invoice',
            'B3' => 'Tanggal Bukti Potong',
            'C3' => 'Nama Klien',
            'D3' => 'Deskripsi',
            'E3' => 'Nilai Invoice',
            'F3' => 'Nominal PPh23',
            'G3' => 'Piutang CoA Terpetakan',
            'H3' => 'Pendapatan CoA Terpetakan',
        ];
        foreach ($pph_checked_headers as $cell => $label) {
            $sheetPphChecked->setCellValue($cell, $label);
        }
        $sheetPphChecked->getStyle('A3:H3')->applyFromArray($headerStyle);

        // Fetch checked invoice items
        $checkedItems = DB::table('invoices as inv')
            ->join('cost_list_invoices as cli', 'cli.invoice_id', '=', 'inv.id')
            ->leftJoin('mous as m', 'm.id', '=', 'inv.mou_id')
            ->leftJoin('clients as c_inv', 'c_inv.id', '=', 'inv.client_id')
            ->leftJoin('clients as c_mou', 'c_mou.id', '=', 'm.client_id')
            ->leftJoin('memos as mem', 'mem.id', '=', 'inv.memo_id')
            ->whereNull('inv.deleted_at')
            ->whereNull('cli.deleted_at')
            ->where('inv.is_pph23_checked', true)
            ->whereBetween('inv.tanggal_bukti_potong_pph23', [$startOfMonth, $endOfMonth])
            // Tarik data invoice yang dicocokkan dengan tanggal bukti potong
            ->selectRaw('
                inv.id as invoice_id,
                inv.invoice_number,
                inv.tanggal_bukti_potong_pph23,
                inv.client_id,
                inv.mou_id,
                inv.memo_id,
                inv.nominal_bukti_potong_pph23,
                cli.description,
                cli.amount as item_amount,
                cli.coa_id,
                c_inv.company_name as client_name_inv,
                c_mou.company_name as client_name_mou,
                mem.nama_klien as memo_nama,
                mem.instansi_klien as memo_instansi
            ')
            ->orderBy('inv.tanggal_bukti_potong_pph23')
            ->get();

        $checkedInvoiceTotals = [];
        foreach ($checkedItems as $item) {
            $checkedInvoiceTotals[$item->invoice_id] = ($checkedInvoiceTotals[$item->invoice_id] ?? 0) + $item->item_amount;
        }

        $cRow = 4;
        $cGrand = 0;

        $piutangToPendapatanMap = [
            188 => 119, // AO-103.6  -> AO-401   (Fee Bulanan)
            182 => 120, // AO-103.7  -> AO-401.1 (Fee SPT)
            183 => 121, // AO-103.8  -> AO-401.2 (Fee SP2DK)
            184 => 122, // AO-103.9  -> AO-401.3 (Fee Pembetulan)
            185 => 123, // AO-103.10 -> AO-401.4 (Fee Internal)
            186 => 124, // AO-103.11 -> AO-401.5 (Fee Restitusi)
            187 => 125, // AO-103.12 -> AO-401.6 (Fee Pemeriksaan)
        ];

        foreach ($checkedItems as $item) {
            $cName = '';
            if ($item->client_name_inv) {
                $cName = $item->client_name_inv;
            } elseif ($item->client_name_mou) {
                $cName = $item->client_name_mou;
            } elseif ($item->memo_nama || $item->memo_instansi) {
                $cName = $item->memo_nama ?? $item->memo_instansi ?? '';
            }

            $mappedCoaId = $item->coa_id;
            if (isset($revenueToPiutangMap[$mappedCoaId])) {
                $pendapatanCoaId = $mappedCoaId;
                $piutangCoaId = $revenueToPiutangMap[$mappedCoaId];
            } elseif (isset($piutangToPendapatanMap[$mappedCoaId])) {
                $piutangCoaId = $mappedCoaId;
                $pendapatanCoaId = $piutangToPendapatanMap[$mappedCoaId];
            } else {
                $piutangCoaId = $mappedCoaId;
                $pendapatanCoaId = $mappedCoaId;
            }

            if ($piutangCoaId == 188 || $piutangCoaId == 182) {
                $piutangCoaId = 188;
                $pendapatanCoaId = 119;
            }

            $piutangCoaRecord = $piutangCoaList->get($piutangCoaId) ?? DB::table('coa')->where('id', $piutangCoaId)->first();
            $piutangLabel = $piutangCoaRecord ? ($piutangCoaRecord->code . ' - ' . $piutangCoaRecord->name) : $piutangCoaId;

            $pendapatanCoaRecord = DB::table('coa')->where('id', $pendapatanCoaId)->first();
            $pendapatanLabel = $pendapatanCoaRecord ? ($pendapatanCoaRecord->code . ' - ' . $pendapatanCoaRecord->name) : $pendapatanCoaId;

            $invoiceTotal = $checkedInvoiceTotals[$item->invoice_id] ?? 0;
            $pphAmount = ($invoiceTotal > 0) ? ($item->item_amount / $invoiceTotal) * $item->nominal_bukti_potong_pph23 : 0;

            $sheetPphChecked->setCellValue('A' . $cRow, $item->invoice_number);
            $sheetPphChecked->setCellValue('B' . $cRow, $item->tanggal_bukti_potong_pph23);
            $sheetPphChecked->setCellValue('C' . $cRow, $cName);
            $sheetPphChecked->setCellValue('D' . $cRow, $item->description);
            $sheetPphChecked->setCellValue('E' . $cRow, $item->item_amount ?: '');
            $sheetPphChecked->setCellValue('F' . $cRow, $pphAmount ?: '');
            $sheetPphChecked->setCellValue('G' . $cRow, $piutangLabel);
            $sheetPphChecked->setCellValue('H' . $cRow, $pendapatanLabel);

            $sheetPphChecked->getStyle('E' . $cRow . ':F' . $cRow)->getNumberFormat()->setFormatCode($numberFmt);
            $cGrand += $pphAmount;
            $cRow++;
        }

        $sheetPphChecked->setCellValue('D' . $cRow, 'TOTAL');
        $sheetPphChecked->setCellValue('F' . $cRow, $cGrand);
        $sheetPphChecked->getStyle('A' . $cRow . ':H' . $cRow)->applyFromArray($totalStyle);
        $sheetPphChecked->getStyle('F' . $cRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sheetPphChecked->getStyle('A3:H' . $cRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

        foreach (range('A', 'H') as $col) {
            $sheetPphChecked->getColumnDimension($col)->setAutoSize(true);
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
