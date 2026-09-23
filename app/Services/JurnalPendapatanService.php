<?php

namespace App\Services;

use App\Models\Coa;
use App\Models\JournalBookReference;
use App\Models\JournalBookReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class JurnalPendapatanService
{
    public const COA_PIUTANG_USAHA_ID = 179;            // AO-103
    public const COA_PENDAPATAN_BELUM_DITERIMA_ID = 175; // AO-208
    public const COA_BIAYA_PPH23_ID = 91;                // AO-108.1 PPh 23
    public const COA_POTONGAN_PENDAPATAN_ID = 190;        // AO-420 Potongan Pendapatan

    public static bool $isSyncing = false;

    /**
     * Dapatkan ID JournalBookReference untuk Jurnal Pendapatan.
     */
    public static function getJurnalPendapatanId(): int
    {
        return (int) (JournalBookReference::where('name', 'Jurnal Pendapatan')->value('id') ?? 4);
    }

    /**
     * Cek apakah JournalBookReference ini adalah Jurnal Pendapatan.
     */
    public static function isJurnalPendapatan(?JournalBookReference $record): bool
    {
        if (!$record) {
            return false;
        }

        return $record->id == static::getJurnalPendapatanId()
            || strcasecmp(trim($record->name), 'Jurnal Pendapatan') === 0;
    }

    /**
     * Mapping CoA Piutang (AO-103.x) ke CoA Pendapatan (AO-401.x).
     */
    public static function getPiutangToPendapatanMap(): array
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
     * Mapping CoA Pendapatan (AO-401.x) ke CoA Piutang (AO-103.x).
     */
    public static function getRevenueToPiutangMap(): array
    {
        return [
            119 => 188, // AO-401   -> AO-103.6  (Fee Bulanan)
            120 => 182, // AO-401.1 -> AO-103.7  (Fee SPT)
            121 => 183, // AO-401.2 -> AO-103.8  (Fee SP2DK)
            122 => 184, // AO-401.3 -> AO-103.9  (Fee Pembetulan)
            123 => 185, // AO-401.4 -> AO-103.10 (Fee Internal)
            124 => 186, // AO-401.5 -> AO-103.11 (Fee Restitusi)
            125 => 187, // AO-401.6 -> AO-103.12 (Fee Pemeriksaan)
        ];
    }

    /**
     * Sumber 1 — Invoices & cost_list_invoices (Invoice dibuat pada bulan berjalan).
     */
    public static function getInvoicePiutangForJP(string $startOfMonth, string $endOfMonth): array
    {
        $rows = DB::table('invoices as inv')
            ->join('cost_list_invoices as cli', 'cli.invoice_id', '=', 'inv.id')
            ->whereNull('inv.deleted_at')
            ->whereNull('cli.deleted_at')
            ->whereNull('inv.memo_id')
            ->whereBetween('inv.invoice_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('cli.coa_id, cli.amount')
            ->get();

        $revenueToPiutangMap    = static::getRevenueToPiutangMap();
        $piutangToPendapatanMap = static::getPiutangToPendapatanMap();

        $byPiutangCoa = [];
        $grandTotal   = 0;

        foreach ($rows as $row) {
            $coaId = $row->coa_id;

            if (isset($revenueToPiutangMap[$coaId])) {
                $piutangCoaId = $revenueToPiutangMap[$coaId];
            } elseif (isset($piutangToPendapatanMap[$coaId])) {
                $piutangCoaId = $coaId;
            } else {
                continue;
            }

            $byPiutangCoa[$piutangCoaId] = ($byPiutangCoa[$piutangCoaId] ?? 0) + (float) $row->amount;
            $grandTotal                  += (float) $row->amount;
        }

        return [
            'by_piutang_coa' => $byPiutangCoa, // JP DEBIT  AO-103.x
            'total'          => $grandTotal,    // JP KREDIT AO-208
        ];
    }

    /**
     * Sumber 2 — cash_reports (CoA AO-103.x yang muncul di kolom kas/bank).
     */
    public static function getCashReportPiutangForJP(string $startOfMonth, string $endOfMonth): array
    {
        $map  = static::getPiutangToPendapatanMap();
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
                $byPendapatanCoa[$pendapatanCoaId] = ($byPendapatanCoa[$pendapatanCoaId] ?? 0) + (float) $row->total;
                $grandTotal                        += (float) $row->total;
            }
        }

        return [
            'by_pendapatan_coa' => $byPendapatanCoa, // JP KREDIT AO-401.x
            'total'             => $grandTotal,       // JP DEBIT  AO-208
        ];
    }

    /**
     * Sumber 3 — Invoices (is_include_pph23 = true, dibuat pada bulan berjalan).
     */
    public static function getInvoicePph23ForJP(string $startOfMonth, string $endOfMonth): array
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

        $revenueToPiutangMap = static::getRevenueToPiutangMap();

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

            $pph23Amount = ((float) $row->amount / 98) * 2;

            $byPiutangCoa[$coaId] = ($byPiutangCoa[$coaId] ?? 0) + $pph23Amount;
            $grandTotal          += $pph23Amount;
        }

        return [
            'by_piutang_coa' => $byPiutangCoa,
            'total'          => $grandTotal,
        ];
    }

    /**
     * Sumber 4 — Invoices (is_pph23_checked = true, dibuat pada bulan berjalan).
     */
    public static function getInvoicePphCheckedForJP(string $startOfMonth, string $endOfMonth): array
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
            $invoiceTotals[$row->invoice_id] = ($invoiceTotals[$row->invoice_id] ?? 0) + (float) $row->item_amount;
        }

        $byPiutangCoa    = [];
        $byPendapatanCoa = [];
        $grandTotal      = 0;

        $revenueToPiutangMap    = static::getRevenueToPiutangMap();
        $piutangToPendapatanMap = static::getPiutangToPendapatanMap();

        foreach ($rows as $row) {
            $coaId = $row->coa_id;

            if (isset($revenueToPiutangMap[$coaId])) {
                $pendapatanCoaId = $coaId;
                $piutangCoaId    = $revenueToPiutangMap[$coaId];
            } elseif (isset($piutangToPendapatanMap[$coaId])) {
                $piutangCoaId    = $coaId;
                $pendapatanCoaId = $piutangToPendapatanMap[$coaId];
            } else {
                $piutangCoaId    = $coaId;
                $pendapatanCoaId = $coaId;
            }

            // Override Bulanan/Tahunan to Bulanan (AO-103.6 / AO-401)
            if ($piutangCoaId == 188 || $piutangCoaId == 182) {
                $piutangCoaId    = 188;
                $pendapatanCoaId = 119;
            }

            $invoiceTotal = $invoiceTotals[$row->invoice_id] ?? 0;
            $pph23Amount  = ($invoiceTotal > 0) ? ((float) $row->item_amount / $invoiceTotal) * (float) $row->nominal_bukti_potong_pph23 : 0;

            $byPiutangCoa[$piutangCoaId]       = ($byPiutangCoa[$piutangCoaId] ?? 0) + $pph23Amount;
            $byPendapatanCoa[$pendapatanCoaId] = ($byPendapatanCoa[$pendapatanCoaId] ?? 0) + $pph23Amount;
            $grandTotal                        += $pph23Amount;
        }

        return [
            'by_piutang_coa'    => $byPiutangCoa,
            'by_pendapatan_coa' => $byPendapatanCoa,
            'total'             => $grandTotal,
        ];
    }

    /**
     * Sumber 5 — MoU discounts (discount_amount > 0 && tgl_discount jatuh pada bulan berjalan).
     */
    public static function getMouDiscountForJP(string $startOfMonth, string $endOfMonth): array
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

        $map = static::getPiutangToPendapatanMap();

        $debits  = [];
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
                    5    => 183,
                    6    => 184,
                    7    => 187,
                    8    => 186,
                    default => 188,
                };
            }

            $pendapatanCoaId = $map[$piutangCoaId] ?? 119;
            $discountAmount  = (float) $mou->discount_amount;

            // Debits:
            // 1. AO-420 (Potongan Pendapatan: 190)
            $debits[self::COA_POTONGAN_PENDAPATAN_ID] = ($debits[self::COA_POTONGAN_PENDAPATAN_ID] ?? 0) + $discountAmount;
            // 2. AO-208 (Pendapatan Yang Belum Diterima: 175)
            $debits[self::COA_PENDAPATAN_BELUM_DITERIMA_ID] = ($debits[self::COA_PENDAPATAN_BELUM_DITERIMA_ID] ?? 0) + $discountAmount;

            // Kredits:
            // 1. AO-103.x (Piutang: $piutangCoaId)
            $kredits[$piutangCoaId] = ($kredits[$piutangCoaId] ?? 0) + $discountAmount;
            // 2. AO-401.x (Pendapatan: $pendapatanCoaId)
            $kredits[$pendapatanCoaId] = ($kredits[$pendapatanCoaId] ?? 0) + $discountAmount;
        }

        return [
            'debits'  => $debits,
            'kredits' => $kredits
        ];
    }

    /**
     * Sumber 6 — MoU cancellations (cancel_mou_amount > 0 && tgl_cancel_mou jatuh pada bulan berjalan).
     */
    public static function getMouCancellationForJP(string $startOfMonth, string $endOfMonth): array
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

        $map = static::getPiutangToPendapatanMap();

        $debits  = [];
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
                    5    => 183,
                    6    => 184,
                    7    => 187,
                    8    => 186,
                    default => 188,
                };
            }

            $cancelAmount = (float) $mou->cancel_mou_amount;

            // Debits:
            // 1. AO-208 (Pendapatan Yang Belum Diterima: 175)
            $debits[self::COA_PENDAPATAN_BELUM_DITERIMA_ID] = ($debits[self::COA_PENDAPATAN_BELUM_DITERIMA_ID] ?? 0) + $cancelAmount;

            // Kredits:
            // 1. AO-103.x (Piutang: $piutangCoaId)
            $kredits[$piutangCoaId] = ($kredits[$piutangCoaId] ?? 0) + $cancelAmount;
        }

        return [
            'debits'  => $debits,
            'kredits' => $kredits
        ];
    }

    /**
     * Generate baris entri jurnal pendapatan ringkasan per kategori & CoA untuk bulan tertentu (Opsi 1).
     * Sesuai dengan Sheet 1 (Ringkasan JP) pada Neraca Lajur Piutang.
     */
    public static function generateJournalEntriesForMonth(int $year, int $month): array
    {
        $startOfMonth     = Carbon::create($year, $month, 1)->startOfMonth()->toDateTimeString();
        $endOfMonth       = Carbon::create($year, $month, 1)->endOfMonth()->toDateTimeString();
        $transactionDate  = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $journalBookId    = static::getJurnalPendapatanId();

        $coas = Coa::all()->keyBy('id');

        $entries = [];

        $addEntry = function (int $coaId, string $description, float $debit, float $credit) use (&$entries, $transactionDate, $journalBookId) {
            if ($debit <= 0 && $credit <= 0) {
                return;
            }

            $entries[] = [
                'transaction_date' => $transactionDate,
                'journal_book_id'  => $journalBookId,
                'coa_id'           => $coaId,
                'description'      => $description,
                'debit_amount'     => round($debit, 2),
                'credit_amount'    => round($credit, 2),
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        };

        // ── Bagian 1: Invoice Dibuat (Pengakuan Piutang) ──
        $invoiceJP = static::getInvoicePiutangForJP($startOfMonth, $endOfMonth);
        foreach ($invoiceJP['by_piutang_coa'] as $coaId => $amount) {
            if ($amount > 0) {
                $coa = $coas->get($coaId);
                $coaLabel = $coa ? "{$coa->code} - {$coa->name}" : "CoA #{$coaId}";
                $addEntry($coaId, "[Invoice Dibuat] Pengakuan Piutang ({$coaLabel})", (float) $amount, 0);
            }
        }
        if ($invoiceJP['total'] > 0) {
            $addEntry(self::COA_PENDAPATAN_BELUM_DITERIMA_ID, "[Invoice Dibuat] Pendapatan Belum Diterima (Total Akrual)", 0, (float) $invoiceJP['total']);
        }

        // ── Bagian 2: Penerimaan Kas (Pengakuan Pendapatan) ──
        $cashJP = static::getCashReportPiutangForJP($startOfMonth, $endOfMonth);
        if ($cashJP['total'] > 0) {
            $addEntry(self::COA_PENDAPATAN_BELUM_DITERIMA_ID, "[Penerimaan Kas] Pendapatan Belum Diterima (Pengurang Akrual)", (float) $cashJP['total'], 0);
        }
        foreach ($cashJP['by_pendapatan_coa'] as $coaId => $amount) {
            if ($amount > 0) {
                $coa = $coas->get($coaId);
                $coaLabel = $coa ? "{$coa->code} - {$coa->name}" : "CoA #{$coaId}";
                $addEntry($coaId, "[Penerimaan Kas] Pengakuan Pendapatan ({$coaLabel})", 0, (float) $amount);
            }
        }

        // ── Bagian 3: PPh 23 Invoice (Pengakuan PPh 23) ──
        $pph23JP = static::getInvoicePph23ForJP($startOfMonth, $endOfMonth);
        foreach ($pph23JP['by_piutang_coa'] as $coaId => $amount) {
            if ($amount > 0) {
                $coa = $coas->get($coaId);
                $coaLabel = $coa ? "{$coa->code} - {$coa->name}" : "CoA #{$coaId}";
                $addEntry($coaId, "[PPh 23 Invoice] Pengakuan PPh 23 ({$coaLabel})", (float) $amount, 0);
            }
        }
        if ($pph23JP['total'] > 0) {
            $addEntry(self::COA_PENDAPATAN_BELUM_DITERIMA_ID, "[PPh 23 Invoice] Pendapatan Belum Diterima (Total PPh 23)", 0, (float) $pph23JP['total']);
        }

        // ── Bagian 4: PPh 23 Checklist (Biaya PPh 23) ──
        $pphCheckedJP = static::getInvoicePphCheckedForJP($startOfMonth, $endOfMonth);
        if ($pphCheckedJP['total'] > 0) {
            $addEntry(self::COA_PENDAPATAN_BELUM_DITERIMA_ID, "[PPh 23 Checklist] Pendapatan Belum Diterima", (float) $pphCheckedJP['total'], 0);
            $addEntry(self::COA_BIAYA_PPH23_ID, "[PPh 23 Checklist] PPh 23 (Biaya)", (float) $pphCheckedJP['total'], 0);
        }
        foreach ($pphCheckedJP['by_pendapatan_coa'] as $coaId => $amount) {
            if ($amount > 0) {
                $coa = $coas->get($coaId);
                $coaLabel = $coa ? "{$coa->code} - {$coa->name}" : "CoA #{$coaId}";
                $addEntry($coaId, "[PPh 23 Checklist] Pengakuan Pendapatan ({$coaLabel})", 0, (float) $amount);
            }
        }
        foreach ($pphCheckedJP['by_piutang_coa'] as $coaId => $amount) {
            if ($amount > 0) {
                $coa = $coas->get($coaId);
                $coaLabel = $coa ? "{$coa->code} - {$coa->name}" : "CoA #{$coaId}";
                $addEntry($coaId, "[PPh 23 Checklist] Pengurangan Piutang ({$coaLabel})", 0, (float) $amount);
            }
        }

        // ── Bagian 5: Diskon dari MoU ──
        $mouDiscountJP = static::getMouDiscountForJP($startOfMonth, $endOfMonth);
        foreach ($mouDiscountJP['debits'] as $coaId => $amount) {
            if ($amount > 0) {
                $coa = $coas->get($coaId);
                $coaLabel = $coa ? "{$coa->code} - {$coa->name}" : "CoA #{$coaId}";
                $addEntry($coaId, "[Diskon MoU] Potongan/Penyesuaian ({$coaLabel})", (float) $amount, 0);
            }
        }
        foreach ($mouDiscountJP['kredits'] as $coaId => $amount) {
            if ($amount > 0) {
                $coa = $coas->get($coaId);
                $coaLabel = $coa ? "{$coa->code} - {$coa->name}" : "CoA #{$coaId}";
                $addEntry($coaId, "[Diskon MoU] Pengurangan Piutang/Pendapatan ({$coaLabel})", 0, (float) $amount);
            }
        }

        // ── Bagian 6: Pembatalan MoU ──
        $mouCancelJP = static::getMouCancellationForJP($startOfMonth, $endOfMonth);
        foreach ($mouCancelJP['debits'] as $coaId => $amount) {
            if ($amount > 0) {
                $coa = $coas->get($coaId);
                $coaLabel = $coa ? "{$coa->code} - {$coa->name}" : "CoA #{$coaId}";
                $addEntry($coaId, "[Pembatalan MoU] Penyesuaian Akrual ({$coaLabel})", (float) $amount, 0);
            }
        }
        foreach ($mouCancelJP['kredits'] as $coaId => $amount) {
            if ($amount > 0) {
                $coa = $coas->get($coaId);
                $coaLabel = $coa ? "{$coa->code} - {$coa->name}" : "CoA #{$coaId}";
                $addEntry($coaId, "[Pembatalan MoU] Pengurangan Piutang ({$coaLabel})", 0, (float) $amount);
            }
        }

        return $entries;
    }

    /**
     * Sinkronkan data jurnal pendapatan untuk bulan dan tahun tertentu ke tabel journal_book_reports.
     */
    public static function syncMonth(int $year, int $month): int
    {
        $journalBookId = static::getJurnalPendapatanId();
        $entries       = static::generateJournalEntriesForMonth($year, $month);

        static::$isSyncing = true;
        try {
            DB::transaction(function () use ($journalBookId, $year, $month, $entries) {
                JournalBookReport::where('journal_book_id', $journalBookId)
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month)
                    ->forceDelete();

                if (!empty($entries)) {
                    JournalBookReport::insert($entries);
                }
            });
        } finally {
            static::$isSyncing = false;
        }

        return count($entries);
    }

    /**
     * Dapatkan semua pasangan (year, month) yang memiliki transaksi piutang/kas/mou.
     */
    public static function getAvailableMonths(): array
    {
        $map = static::getPiutangToPendapatanMap();
        $piutangCoaIds = array_keys($map);

        $invMonths = DB::table('invoices')
            ->whereNull('deleted_at')
            ->whereNotNull('invoice_date')
            ->selectRaw('DISTINCT YEAR(invoice_date) as y, MONTH(invoice_date) as m')
            ->get();

        $cashMonths = DB::table('cash_reports')
            ->whereNull('deleted_at')
            ->whereIn('coa_id', $piutangCoaIds)
            ->whereNotNull('transaction_date')
            ->selectRaw('DISTINCT YEAR(transaction_date) as y, MONTH(transaction_date) as m')
            ->get();

        $mouDiscountMonths = DB::table('mous')
            ->whereNull('deleted_at')
            ->where('status', 'approved')
            ->where('discount_amount', '>', 0)
            ->whereNotNull('tgl_discount')
            ->selectRaw('DISTINCT YEAR(tgl_discount) as y, MONTH(tgl_discount) as m')
            ->get();

        $mouCancelMonths = DB::table('mous')
            ->whereNull('deleted_at')
            ->where('status', 'approved')
            ->where('cancel_mou_amount', '>', 0)
            ->whereNotNull('tgl_cancel_mou')
            ->selectRaw('DISTINCT YEAR(tgl_cancel_mou) as y, MONTH(tgl_cancel_mou) as m')
            ->get();

        $combined = collect()
            ->concat($invMonths)
            ->concat($cashMonths)
            ->concat($mouDiscountMonths)
            ->concat($mouCancelMonths)
            ->unique(fn($item) => $item->y . '-' . $item->m)
            ->sortBy(['y', 'm'])
            ->values();

        return $combined->map(fn($item) => [
            'year'  => (int) $item->y,
            'month' => (int) $item->m,
        ])->toArray();
    }

    /**
     * Sinkronkan semua bulan yang memiliki data transaksi ke tabel journal_book_reports.
     */
    public static function syncAll(): int
    {
        $months     = static::getAvailableMonths();
        $totalCount = 0;

        foreach ($months as $item) {
            $totalCount += static::syncMonth($item['year'], $item['month']);
        }

        return $totalCount;
    }
}
