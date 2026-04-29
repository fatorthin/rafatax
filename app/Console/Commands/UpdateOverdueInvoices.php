<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Invoice;

class UpdateOverdueInvoices extends Command
{
    /**
     * Nama & signature command.
     * Bisa dipanggil manual: php artisan invoices:update-overdue
     * Atau dengan flag dry-run: php artisan invoices:update-overdue --dry-run
     */
    protected $signature = 'invoices:update-overdue
                            {--dry-run : Tampilkan invoice yang akan diupdate tanpa benar-benar menyimpan perubahan}';

    protected $description = 'Update status invoice menjadi "overdue" jika due_date sudah lewat dan status belum "paid"';

    public function handle(): int
    {
        $today    = Carbon::today();
        $isDryRun = $this->option('dry-run');

        $this->info("📅 Tanggal hari ini : {$today->toDateString()}");
        $this->info($isDryRun ? '🔍 Mode: DRY RUN (tidak ada perubahan yang disimpan)' : '🔄 Mode: LIVE UPDATE');
        $this->newLine();

        // Ambil invoice yang sudah melewati due_date dan bukan paid / overdue
        $invoices = Invoice::whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->whereNotIn('invoice_status', ['paid', 'overdue'])
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('✅ Tidak ada invoice yang perlu diupdate.');
            return self::SUCCESS;
        }

        $this->info("🔎 Ditemukan {$invoices->count()} invoice yang akan diupdate:");
        $this->newLine();

        $headers = ['ID', 'No. Invoice', 'Due Date', 'Status Lama'];
        $rows    = $invoices->map(fn($inv) => [
            $inv->id,
            $inv->invoice_number,
            $inv->due_date,
            $inv->invoice_status,
        ])->toArray();

        $this->table($headers, $rows);
        $this->newLine();

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN aktif — tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        // Lakukan update
        $updated = Invoice::whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->whereNotIn('invoice_status', ['paid', 'overdue'])
            ->update(['invoice_status' => 'overdue']);

        $this->info("✅ Berhasil mengupdate {$updated} invoice menjadi status 'overdue'.");

        return self::SUCCESS;
    }
}
