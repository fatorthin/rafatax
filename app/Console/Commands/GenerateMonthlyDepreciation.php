<?php

namespace App\Console\Commands;

use App\Services\DepreciationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyDepreciation extends Command
{
    /**
     * Nama & signature command.
     * Manual: php artisan aktiva:generate-depreciation
     * Dengan periode: php artisan aktiva:generate-depreciation --periode=2026-08
     */
    protected $signature = 'aktiva:generate-depreciation
                            {--periode= : Periode depresiasi format YYYY-MM (opsional, default: bulan berjalan)}';

    protected $description = 'Hitung depresiasi aktiva tetap dan catat jurnal bulanan secara otomatis';

    public function handle(DepreciationService $service): int
    {
        $periodeOption = $this->option('periode');

        if ($periodeOption) {
            try {
                $date = Carbon::createFromFormat('Y-m', $periodeOption)->endOfMonth();
            } catch (\Exception $e) {
                $this->error("Format periode tidak valid! Gunakan format YYYY-MM (contoh: 2026-08).");
                return self::FAILURE;
            }
        } else {
            $now = Carbon::now();
            // Jika dieksekusi pada awal bulan (tgl 1-5), otomatis default ke penutupan bulan sebelumnya
            if ($now->day <= 5) {
                $date = $now->copy()->subMonth()->endOfMonth();
            } else {
                $date = $now->endOfMonth();
            }
        }

        $this->info("🔄 Memproses depresiasi aktiva tetap untuk periode: {$date->format('F Y')}...");

        $result = $service->generateForDate($date);

        $this->info("✅ Berhasil memproses depresiasi aktiva tetap.");
        $this->line("   - Aset baru terhitung: {$result['count']}");
        $this->line("   - Total penyusutan periode ini: Rp " . number_format($result['total'], 0, ',', '.'));

        return self::SUCCESS;
    }
}
