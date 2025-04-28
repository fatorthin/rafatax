<?php

namespace Database\Seeders;

use App\Models\CashReport;
use App\Models\Invoice;
use App\Models\CashReference;
use App\Models\Coa;
use Illuminate\Database\Seeder;

class CashReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if we have existing data required for cash reports
        $invoiceCount = Invoice::count();
        $coaCount = Coa::count();
        $cashRefCount = CashReference::count();

        if ($invoiceCount > 0 && $coaCount > 0 && $cashRefCount > 0) {
            // We have existing data, create 1000 cash reports
            $this->command->info('Generating 1000 cash reports...');
            
            // Create records in batches to avoid memory issues
            $batchSize = 100;
            for ($i = 0; $i < 10; $i++) {
                $this->command->info("Generating batch " . ($i + 1) . " of 10...");
                
                // Create some income transactions
                CashReport::factory()
                    ->income()
                    ->count(50)
                    ->create();
                
                // Create some expense transactions
                CashReport::factory()
                    ->expense()
                    ->count(30)
                    ->create();
                
                // Create some mixed transactions
                CashReport::factory()
                    ->count(20)
                    ->create();
            }
            
            $this->command->info('Finished generating cash reports.');
        } else {
            $this->command->warn('Not enough data to generate cash reports. Make sure you have invoices, COAs, and cash references.');
        }
    }
} 