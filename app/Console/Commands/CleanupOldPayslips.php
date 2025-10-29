<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupOldPayslips extends Command
{
    protected $signature = 'payslips:cleanup {--days=7 : Days to keep payslips}';
    protected $description = 'Clean up old payslip PDFs from public storage';

    public function handle()
    {
        $days = $this->option('days');
        $path = public_path('storage/payslips/');

        if (!file_exists($path)) {
            $this->info('No payslips directory found.');
            return 0;
        }

        $files = glob($path . '*.pdf');
        $cutoff = time() - ($days * 24 * 60 * 60);
        $deleted = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        $this->info("Cleaned up {$deleted} old payslip(s) older than {$days} days.");
        return 0;
    }
}
