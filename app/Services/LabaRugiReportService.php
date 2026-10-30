<?php

namespace App\Services;

use App\Models\Coa;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LabaRugiReportService
{
    private const LABA_RUGI_GROUP_IDS = [40, 50, 60, 70];
    private const PENDAPATAN_GROUP_IDS = [40, 60];
    private const LUAR_USAHA_GROUP_IDS = [60, 70];

    public function getMonthlyReport(int $month, int $year, string $entity = 'all'): array
    {
        $startOfPreviousMonth = Carbon::create($year, $month, 1)->subMonth()->startOfMonth();
        $endOfPreviousMonth = Carbon::create($year, $month, 1)->subMonth()->endOfMonth();
        $startOfCurrentMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfCurrentMonth = Carbon::create($year, $month, 1)->endOfMonth();

        $startOfPreviousMonthString = $startOfPreviousMonth->toDateTimeString();
        $endOfPreviousMonthString = $endOfPreviousMonth->toDateTimeString();
        $startOfCurrentMonthString = $startOfCurrentMonth->toDateTimeString();
        $endOfCurrentMonthString = $endOfCurrentMonth->toDateTimeString();

        $isSeparated = ($year > 2026) || ($year == 2026 && $month >= 4);
        $ent = strtolower($entity);

        $depresiasiQuery = \App\Models\DepresiasiAktivaTetap::query()
            ->whereYear('tanggal_penyusutan', '=', $year)
            ->whereMonth('tanggal_penyusutan', '=', $month);

        if ($isSeparated && $ent !== 'all') {
            $depresiasiQuery->whereHas('daftarAktivaTetap', function ($q) use ($ent) {
                $q->where('kepemilikan', strtoupper($ent));
            });
        }

        $depresiasiTotal = $depresiasiQuery->sum('jumlah_penyusutan') ?? 0;

        // Tentukan kondisi rekening Kas / Bank berdasarkan pemisahan entitas
        if ($isSeparated && $ent === 'pt') {
            $kasBesarCondition = "cash_reference_id = 0"; // PT tidak memiliki kas besar
            $kasKecilCondition = "cash_reference_id = 9"; // Kas Kecil PT
            $bankCondition     = "cash_reference_id IN (1, 4)"; // BCA PT & BCA 436
        } elseif ($isSeparated && $ent === 'kkp') {
            $kasBesarCondition = "cash_reference_id = 6"; // Kas Besar KKP
            $kasKecilCondition = "cash_reference_id = 7"; // Kas Kecil KKP
            $bankCondition     = "cash_reference_id IN (2)"; // BCA 425
        } else {
            $kasBesarCondition = "cash_reference_id = 6";
            $kasKecilCondition = "cash_reference_id IN (7, 9)";
            if ($year == 2026 && $month < 4) {
                $bankCondition = "cash_reference_id IN (1, 2, 3, 4)";
            } elseif ($year < 2026) {
                $bankCondition = "cash_reference_id IN (1, 2, 3, 4, 5)";
            } else {
                $bankCondition = "cash_reference_id IN (1, 2, 4)";
            }
        }

        // Tentukan filter kepemilikan untuk jurnal pembukuan
        $journalEntityFilter = "";
        if ($isSeparated && $ent === 'pt') {
            $journalEntityFilter = "AND UPPER(kepemilikan) = 'PT'";
        } elseif ($isSeparated && $ent === 'kkp') {
            $journalEntityFilter = "AND (kepemilikan IS NULL OR UPPER(kepemilikan) = 'KKP')";
        }

        $data = Coa::query()
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
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_debit, 0) as jurnal_umum_debit'),
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_kredit, 0) as jurnal_umum_kredit'),
                DB::raw('COALESCE(jurnal_pendapatan_data.jurnal_pendapatan_debit, 0) as jurnal_pendapatan_debit'),
                DB::raw('COALESCE(jurnal_pendapatan_data.jurnal_pendapatan_kredit, 0) as jurnal_pendapatan_kredit'),
                DB::raw("COALESCE(aje_data.aje_debit, 0) + (CASE WHEN coa.code = 'AO-509' THEN {$depresiasiTotal} ELSE 0 END) as aje_debit"),
                DB::raw("COALESCE(aje_data.aje_kredit, 0) + (CASE WHEN coa.code = 'AO-127' THEN {$depresiasiTotal} ELSE 0 END) as aje_kredit")
            ])
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(debit_amount) as neraca_awal_debit,
                        SUM(credit_amount) as neraca_awal_kredit
                    FROM journal_book_reports 
                    WHERE transaction_date BETWEEN '{$startOfPreviousMonthString}' AND '{$endOfPreviousMonthString}'
                    AND deleted_at IS NULL
                    AND journal_book_id = 3
                    {$journalEntityFilter}
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
                        SUM(credit_amount) as kas_besar_debit,
                        SUM(debit_amount) as kas_besar_kredit
                    FROM cash_reports
                    WHERE {$kasBesarCondition}
                    AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as kas_besar_data"),
                'coa.id',
                '=',
                'kas_besar_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(credit_amount) as kas_kecil_debit,
                        SUM(debit_amount) as kas_kecil_kredit
                    FROM cash_reports
                    WHERE {$kasKecilCondition}
                    AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as kas_kecil_data"),
                'coa.id',
                '=',
                'kas_kecil_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(credit_amount) as bank_debit,
                        SUM(debit_amount) as bank_kredit
                    FROM cash_reports
                    WHERE {$bankCondition}
                    AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
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
                    AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                    AND deleted_at IS NULL
                    {$journalEntityFilter}
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
                    AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                    AND deleted_at IS NULL
                    {$journalEntityFilter}
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
                        SUM(debit_amount) as jurnal_pendapatan_debit,
                        SUM(credit_amount) as jurnal_pendapatan_kredit
                    FROM journal_book_reports 
                    WHERE journal_book_id = 4
                    AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                    AND deleted_at IS NULL
                    {$journalEntityFilter}
                    GROUP BY coa_id
                ) as jurnal_pendapatan_data"),
                'coa.id',
                '=',
                'jurnal_pendapatan_data.coa_id'
            )
            ->whereNull('coa.deleted_at')
            ->where('coa.type', 'kkp')
            ->whereIn('coa.group_coa_id', self::LABA_RUGI_GROUP_IDS)
            ->orderBy('coa.code')
            ->get();

        $items = [];
        $totalPendapatan = 0;
        $totalBeban = 0;

        foreach ($data as $row) {
            $totalDebit = $row->neraca_awal_debit + $row->kas_besar_debit +
                $row->kas_kecil_debit + $row->bank_debit +
                $row->jurnal_umum_debit + $row->jurnal_pendapatan_debit + $row->aje_debit;

            $totalKredit = $row->neraca_awal_kredit + $row->kas_besar_kredit +
                $row->kas_kecil_kredit + $row->bank_kredit +
                $row->jurnal_umum_kredit + $row->jurnal_pendapatan_kredit + $row->aje_kredit;

            if (in_array($row->group_coa_id, self::PENDAPATAN_GROUP_IDS, true)) {
                $amount = $totalKredit - $totalDebit;
                $totalPendapatan += $amount;
                $category = in_array($row->group_coa_id, self::LUAR_USAHA_GROUP_IDS, true)
                    ? 'Penghasilan (Biaya) Luar Usaha'
                    : 'Pendapatan';
            } else {
                $amount = $totalDebit - $totalKredit;
                $totalBeban += $amount;
                $category = in_array($row->group_coa_id, self::LUAR_USAHA_GROUP_IDS, true)
                    ? 'Penghasilan (Biaya) Luar Usaha'
                    : 'Beban';
            }

            $items[] = [
                'code' => $row->code,
                'name' => $row->name,
                'category' => $category,
                'amount' => $amount,
                'is_negative' => $amount < 0,
            ];
        }

        return [
            'items' => collect($items)->sortBy('code')->values(),
            'totalPendapatan' => abs($totalPendapatan),
            'totalBeban' => abs($totalBeban),
            'labaRugiBersih' => $totalPendapatan - $totalBeban,
        ];
    }
}
