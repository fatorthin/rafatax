<?php

namespace App\Services;

use App\Models\Coa;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LabaRugiReportService
{
    public function getMonthlyReport(int $month, int $year): array
    {
        $startOfPreviousMonth = Carbon::create($year, $month, 1)->subMonth()->startOfMonth();
        $endOfPreviousMonth = Carbon::create($year, $month, 1)->subMonth()->endOfMonth();
        $startOfCurrentMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfCurrentMonth = Carbon::create($year, $month, 1)->endOfMonth();

        $startOfPreviousMonthString = $startOfPreviousMonth->toDateTimeString();
        $endOfPreviousMonthString = $endOfPreviousMonth->toDateTimeString();
        $startOfCurrentMonthString = $startOfCurrentMonth->toDateTimeString();
        $endOfCurrentMonthString = $endOfCurrentMonth->toDateTimeString();

        $data = Coa::query()
            ->select([
                'coa.id',
                'coa.code',
                'coa.name',
                'coa.type',
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
                DB::raw('COALESCE(aje_data.aje_debit, 0) as aje_debit'),
                DB::raw('COALESCE(aje_data.aje_kredit, 0) as aje_kredit')
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
                    WHERE cash_reference_id = 6
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
                    WHERE cash_reference_id = 7
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
                    WHERE cash_reference_id IN (1,2,3,4,5)
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
                    GROUP BY coa_id
                ) as aje_data"),
                'coa.id',
                '=',
                'aje_data.coa_id'
            )
            ->whereNull('coa.deleted_at')
            ->where('coa.type', 'kkp')
            ->whereRaw("coa.code REGEXP '^AO-(4[0-9]{2}(\\.[1-6])?|501(\\.[1-4])?|50[0-9]|5[1-9][0-9]|6[0-9]{2}|70[0-2])$'")
            ->orderBy('coa.code')
            ->get();

        $items = [];
        $totalPendapatan = 0;
        $totalBeban = 0;

        foreach ($data as $row) {
            $totalDebit = $row->neraca_awal_debit + $row->kas_besar_debit +
                $row->kas_kecil_debit + $row->bank_debit +
                $row->jurnal_umum_debit + $row->aje_debit;

            $totalKredit = $row->neraca_awal_kredit + $row->kas_besar_kredit +
                $row->kas_kecil_kredit + $row->bank_kredit +
                $row->jurnal_umum_kredit + $row->aje_kredit;

            if (preg_match('/^AO-4/', $row->code)) {
                $amount = $totalKredit - $totalDebit;
                $totalPendapatan += $amount;
                $category = 'Pendapatan';
            } elseif (preg_match('/^AO-6/', $row->code)) {
                $amount = $totalKredit - $totalDebit;
                $totalPendapatan += $amount;
                $category = 'Penghasilan (Biaya) Luar Usaha';
            } elseif (preg_match('/^AO-7/', $row->code)) {
                $amount = $totalDebit - $totalKredit;
                $totalBeban += $amount;
                $category = 'Penghasilan (Biaya) Luar Usaha';
            } else {
                $amount = $totalDebit - $totalKredit;
                $totalBeban += $amount;
                $category = 'Beban';
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
