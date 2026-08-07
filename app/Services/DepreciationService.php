<?php

namespace App\Services;

use App\Models\DaftarAktivaTetap;
use App\Models\DepresiasiAktivaTetap;
use App\Models\JournalBookReport;
use Carbon\Carbon;

class DepreciationService
{
    /**
     * Generate monthly depreciation for active assets for a given date.
     *
     * @param Carbon $date
     * @return array{count: int, total: float}
     */
    public function generateForDate(Carbon $date): array
    {
        $assets = DaftarAktivaTetap::query()->where('status', 'aktif')->get();
        $count = 0;

        foreach ($assets as $asset) {
            // Skip aset yang belum diperoleh pada periode yang dipilih
            $acquisitionDate = Carbon::parse($asset->tahun_perolehan)->startOfMonth();
            if ($date->copy()->startOfMonth()->lt($acquisitionDate)) {
                continue;
            }

            // Hitung total penyusutan yang sudah ada
            $existingDepreciation = DepresiasiAktivaTetap::query()
                ->where('daftar_aktiva_tetap_id', $asset->id)
                ->sum('jumlah_penyusutan');

            // Hitung sisa nilai buku saat ini
            $remainingValue = $asset->harga_perolehan - $existingDepreciation;

            // Jika sisa nilai buku sudah 0 atau kurang, skip
            if ($remainingValue <= 0) {
                continue;
            }

            // Hitung penyusutan bulanan: (Harga Perolehan * Tarif) / 100 / 12
            $monthlyDepreciation = ($asset->harga_perolehan * $asset->tarif_penyusutan / 100) / 12;
            $monthlyDepreciation = round($monthlyDepreciation);

            // Pastikan penyusutan tidak melebihi sisa nilai buku
            if ($monthlyDepreciation > $remainingValue) {
                $monthlyDepreciation = $remainingValue;
            }

            // Cek apakah sudah ada depresiasi di bulan dan tahun yang sama (self-healing)
            $existingRecords = DepresiasiAktivaTetap::query()
                ->where('daftar_aktiva_tetap_id', $asset->id)
                ->whereYear('tanggal_penyusutan', $date->year)
                ->whereMonth('tanggal_penyusutan', $date->month)
                ->orderBy('id')
                ->get();

            if ($existingRecords->count() > 0) {
                // Hapus duplikasi data jika ada lebih dari 1
                if ($existingRecords->count() > 1) {
                    $duplicates = $existingRecords->slice(1);
                    foreach ($duplicates as $duplicate) {
                        $duplicate->delete();
                    }
                }
                continue;
            }

            DepresiasiAktivaTetap::create([
                'daftar_aktiva_tetap_id' => $asset->id,
                'tanggal_penyusutan' => $date->format('Y-m-d'),
                'jumlah_penyusutan' => $monthlyDepreciation,
            ]);

            $count++;
        }

        $totalDepreciationAll = DepresiasiAktivaTetap::query()
            ->whereYear('tanggal_penyusutan', $date->year)
            ->whereMonth('tanggal_penyusutan', $date->month)
            ->sum('jumlah_penyusutan');

        if ($totalDepreciationAll > 0) {
            $month = $date->month;
            $year = $date->year;
            $transactionDate = $date->copy()->endOfMonth()->format('Y-m-d');
            $descriptionDebit = "Beban Depresiasi Aktiva Tetap " . $date->format('M Y');
            $descriptionKredit = "Akumulasi Depresiasi Aktiva Tetap " . $date->format('M Y');

            // CoA 139 (Debit)
            $journalDebit = JournalBookReport::where('coa_id', 139)
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->first();

            if (!$journalDebit) {
                JournalBookReport::create([
                    'description' => $descriptionDebit,
                    'journal_book_id' => 2, // AJE
                    'debit_amount' => $totalDepreciationAll,
                    'credit_amount' => 0,
                    'coa_id' => 139,
                    'transaction_date' => $transactionDate,
                ]);
            } elseif ($journalDebit->debit_amount != $totalDepreciationAll) {
                $journalDebit->update([
                    'debit_amount' => $totalDepreciationAll,
                    'credit_amount' => 0,
                ]);
            }

            // CoA 103 (Kredit)
            $journalKredit = JournalBookReport::where('coa_id', 103)
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->first();

            if (!$journalKredit) {
                JournalBookReport::create([
                    'description' => $descriptionKredit,
                    'journal_book_id' => 2, // AJE
                    'debit_amount' => 0,
                    'credit_amount' => $totalDepreciationAll,
                    'coa_id' => 103,
                    'transaction_date' => $transactionDate,
                ]);
            } elseif ($journalKredit->credit_amount != $totalDepreciationAll) {
                $journalKredit->update([
                    'debit_amount' => 0,
                    'credit_amount' => $totalDepreciationAll,
                ]);
            }
        }

        return [
            'count' => $count,
            'total' => $totalDepreciationAll,
        ];
    }
}
