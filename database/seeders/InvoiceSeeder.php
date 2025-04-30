<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\MoU;
use App\Models\CostListInvoice;
use App\Models\CostListMou;
use App\Models\Coa;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some of the MoUs to attach invoices to
        $mous = MoU::all()->take(1000);
        $allInvoices = collect();

        // If we have MoUs, create invoices for them
        if ($mous->count() > 0) {
            foreach ($mous as $mou) {
                // Create 1-3 invoices for each MoU
                $invoiceCount = rand(1, 3);
                $invoices = Invoice::factory()->count($invoiceCount)->create([
                    'mou_id' => $mou->id,
                ]);
                
                $allInvoices = $allInvoices->merge($invoices);
            }
        }

        // Create 5 paid invoices
        $paidInvoices = Invoice::factory()->paid()->count(5)->create();
        $allInvoices = $allInvoices->merge($paidInvoices);
        
        // Create 3 overdue invoices
        $overdueInvoices = Invoice::factory()->overdue()->count(3)->create();
        $allInvoices = $allInvoices->merge($overdueInvoices);

        // Get all existing CoAs or create some if needed
        $coas = Coa::all();
        if ($coas->count() < 5) {
            // Create some CoAs if we don't have enough
            $coas = $coas->merge(Coa::factory()->count(5 - $coas->count())->create());
        }

        // Create cost list items for each invoice
        foreach ($allInvoices as $invoice) {
            $mou = $invoice->mou;
            
            if ($mou) {
                // If there are existing cost list items for the MoU, use some of them
                $costListMous = CostListMou::where('mou_id', $mou->id)->get();
                
                if ($costListMous->count() > 0) {
                    // Use 1-3 cost list items from the MoU or all if fewer are available
                    $itemCount = min(rand(1, 3), $costListMous->count());
                    $selectedCostItems = $costListMous->random($itemCount);
                    
                    foreach ($selectedCostItems as $costItem) {
                        // Create invoice cost item based on the MoU cost item
                        CostListInvoice::factory()->create([
                            'invoice_id' => $invoice->id,
                            'mou_id' => $mou->id,
                            'coa_id' => $costItem->coa_id,
                            'amount' => $costItem->amount,
                            'description' => $costItem->description,
                        ]);
                    }
                } else {
                    // No existing cost items, create new ones
                    $this->createRandomCostItems($invoice, $mou, $coas);
                }
            } else {
                // No MoU, create new random cost items
                $this->createRandomCostItems($invoice, null, $coas);
            }
        }
    }

    /**
     * Create random cost list items for an invoice
     */
    private function createRandomCostItems($invoice, $mou, $coas)
    {
        // Each invoice will have 1-4 cost list items
        $costItemCount = rand(1, 4);
        
        // Get random CoAs for this invoice
        $invoiceCoas = $coas->random(min($costItemCount, $coas->count()));
        
        foreach ($invoiceCoas as $coa) {
            CostListInvoice::factory()->create([
                'invoice_id' => $invoice->id,
                'mou_id' => $mou ? $mou->id : ($invoice->mou_id ?? MoU::factory()->create()->id),
                'coa_id' => $coa->id,
                'amount' => rand(500000, 20000000),
            ]);
        }
    }
} 