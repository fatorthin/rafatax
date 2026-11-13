<?php

namespace App\Services;

use App\Filament\Resources\CashReportResource\Pages\NeracaLajurPiutang;
use App\Models\JournalBookReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NeracaLajurCutOffService
{
    /**
     * Jalankan Cut Off Neraca untuk bulan, tahun, dan entitas tertentu.
     *
     * @param int $year
     * @param int $month
     * @param string $entity 'all' | 'pt' | 'kkp'
     * @param string|null $targetMode Untuk konsolidasi terpisah: 'both' | 'pt' | 'kkp'
     * @return array
     */
    public static function cutOff(int $year, int $month, string $entity = 'all', ?string $targetMode = null): array
    {
        $entity = strtolower($entity);
        $isSeparated = ($year > 2026) || ($year == 2026 && $month >= 4);

        // Kasus: LK Konsolidasi pada masa pemisahan entitas aktif (>= April 2026)
        if ($isSeparated && $entity === 'all') {
            $mode = $targetMode ?? 'both';
            $results = [];

            if ($mode === 'both' || $mode === 'pt') {
                $results['pt'] = static::processSingleCutOff($year, $month, 'pt');
            }
            if ($mode === 'both' || $mode === 'kkp') {
                $results['kkp'] = static::processSingleCutOff($year, $month, 'kkp');
            }

            $totalInserted = array_sum(array_column($results, 'inserted_count'));

            $entityLabels = [];
            if (isset($results['pt'])) {
                $entityLabels[] = "PT ({$results['pt']['inserted_count']} akun)";
            }
            if (isset($results['kkp'])) {
                $entityLabels[] = "KKP ({$results['kkp']['inserted_count']} akun)";
            }
            $labelStr = implode(' & ', $entityLabels);

            return [
                'success'        => true,
                'entity'         => 'all',
                'mode'           => $mode,
                'total_inserted' => $totalInserted,
                'details'        => $results,
                'message'        => "Cut Off Neraca Konsolidasi berhasil disimpan untuk {$labelStr}. Total {$totalInserted} akun tersimpan ke Neraca Bulanan.",
            ];
        }

        // Kasus: LK Bukan Konsolidasi (PT / KKP) atau Konsolidasi sebelum masa pemisahan (< April 2026)
        $res = static::processSingleCutOff($year, $month, $entity);
        $entityLabel = strtoupper($entity === 'all' ? 'Konsolidasi' : $entity);

        return [
            'success'        => true,
            'entity'         => $entity,
            'total_inserted' => $res['inserted_count'],
            'details'        => [$entity => $res],
            'message'        => "Cut Off Neraca {$entityLabel} berhasil disimpan ({$res['inserted_count']} akun tersimpan ke Neraca Bulanan).",
        ];
    }

    /**
     * Proses cut off untuk entitas tunggal ('pt', 'kkp', atau 'all' sebelum masa pemisahan).
     */
    public static function processSingleCutOff(int $year, int $month, string $entity): array
    {
        $entity = strtolower($entity);
        $isSeparated = ($year > 2026) || ($year == 2026 && $month >= 4);
        $transactionDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        // 1. Dapatkan data Trial Balance dari NeracaLajurPiutang
        $page = new NeracaLajurPiutang();
        $page->month  = $month;
        $page->year   = $year;
        $page->entity = $entity;

        $data = $page->getTableData();
        $lrCalc = $page->getLabaRugiData();
        $labaRugiBersih = (float) ($lrCalc['labaRugiBersih'] ?? 0);

        // Tentukan target kepemilikan
        $kepemilikanVal = ($isSeparated && in_array($entity, ['pt', 'kkp'], true)) ? strtoupper($entity) : 'KKP';

        $recordsToInsert = [];

        foreach ($data as $item) {
            // Hanya akun Neraca (Aktiva, Kewajiban, Ekuitas: group 10, 11, 12, 20, 21, 30)
            if (!in_array((int) $item->group_coa_id, [10, 11, 12, 20, 21, 30], true)) {
                continue;
            }

            // Exclude AO-101.2 (transit/contra kas bank)
            if ($item->code === 'AO-101.2') {
                continue;
            }

            // Penanganan khusus akun AO-305 (Laba Rugi Tahun Berjalan)
            if ($item->code === 'AO-305' || $item->id == 118) {
                if ($labaRugiBersih != 0) {
                    $recordsToInsert[] = [
                        'description'      => $item->code . ' - ' . $item->name,
                        'journal_book_id'  => 3,
                        'debit_amount'     => max(0, -$labaRugiBersih),
                        'credit_amount'    => max(0, $labaRugiBersih),
                        'coa_id'           => $item->id,
                        'transaction_date' => $transactionDate,
                        'kepemilikan'      => $kepemilikanVal,
                    ];
                }
                continue;
            }

            // Akun Neraca lainnya: hitung saldo setelah AJE
            $totalDebit = (float) $item->neraca_awal_debit + (float) $item->kas_besar_debit +
                (float) $item->kas_kecil_debit + (float) $item->bank_debit +
                (float) $item->jurnal_pendapatan_debit + (float) $item->jurnal_umum_debit;

            $totalKredit = (float) $item->neraca_awal_kredit + (float) $item->kas_besar_kredit +
                (float) $item->kas_kecil_kredit + (float) $item->bank_kredit +
                (float) $item->jurnal_pendapatan_kredit + (float) $item->jurnal_umum_kredit;

            $selisihSebelumAJE = $totalDebit - $totalKredit;
            $selisihSetelahAJE = $selisihSebelumAJE + ((float) $item->aje_debit - (float) $item->aje_kredit);

            $selisihRound = round($selisihSetelahAJE, 2);

            if ($selisihRound != 0) {
                $recordsToInsert[] = [
                    'description'      => $item->code . ' - ' . $item->name,
                    'journal_book_id'  => 3,
                    'debit_amount'     => max(0, $selisihRound),
                    'credit_amount'    => max(0, -$selisihRound),
                    'coa_id'           => $item->id,
                    'transaction_date' => $transactionDate,
                    'kepemilikan'      => $kepemilikanVal,
                ];
            }
        }

        // Simpan dalam transaksi DB
        DB::transaction(function () use ($year, $month, $entity, $isSeparated, $recordsToInsert, $kepemilikanVal) {
            $deleteQuery = JournalBookReport::where('journal_book_id', 3)
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month);

            if ($isSeparated && in_array($entity, ['pt', 'kkp'], true)) {
                $targetEnt = strtoupper($entity);
                if ($targetEnt === 'PT') {
                    $deleteQuery->whereRaw('UPPER(kepemilikan) = ?', ['PT']);
                } else {
                    $deleteQuery->whereRaw('(kepemilikan IS NULL OR UPPER(kepemilikan) = ?)', ['KKP']);
                }
            } elseif (!$isSeparated) {
                // Sebelum masa pemisahan, hapus semua record cut-off bulan tsb
            } else {
                $deleteQuery->whereRaw('(kepemilikan = ? OR kepemilikan IS NULL)', [$kepemilikanVal]);
            }

            // Hapus record lama agar digantikan dengan yang baru
            $deleteQuery->forceDelete();

            foreach ($recordsToInsert as $row) {
                JournalBookReport::create($row);
            }
        });

        return [
            'entity'          => $entity,
            'kepemilikan'     => $kepemilikanVal,
            'inserted_count'  => count($recordsToInsert),
            'laba_rugi_bersih' => $labaRugiBersih,
        ];
    }

    /**
     * Cek apakah sudah ada data cut-off untuk periode dan entitas ini.
     */
    public static function getCutOffStatus(int $year, int $month, string $entity = 'all'): array
    {
        $entity = strtolower($entity);
        $isSeparated = ($year > 2026) || ($year == 2026 && $month >= 4);

        $query = JournalBookReport::where('journal_book_id', 3)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month);

        if ($isSeparated && in_array($entity, ['pt', 'kkp'], true)) {
            $targetEnt = strtoupper($entity);
            if ($targetEnt === 'PT') {
                $query->whereRaw('UPPER(kepemilikan) = ?', ['PT']);
            } else {
                $query->whereRaw('(kepemilikan IS NULL OR UPPER(kepemilikan) = ?)', ['KKP']);
            }
        }

        $count = $query->count();
        return [
            'is_cutoff' => $count > 0,
            'count'     => $count,
        ];
    }
}

