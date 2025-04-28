<?php

namespace Database\Factories;

use App\Models\CashReport;
use App\Models\CashReference;
use App\Models\Coa;
use App\Models\Invoice;
use App\Models\MoU;
use App\Models\CostListInvoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashReportFactory extends Factory
{
    protected $model = CashReport::class;

    public function definition()
    {
        // Get random existing data or create new ones
        $invoice = Invoice::inRandomOrder()->first();
        $cashReference = CashReference::inRandomOrder()->first();
        $coa = Coa::inRandomOrder()->first();
        
        // Determine if this is a debit or credit transaction
        $isDebit = $this->faker->boolean(70); // 70% chance of being a debit transaction
        
        // Set appropriate amounts
        $debitAmount = $isDebit ? $this->faker->numberBetween(100000, 10000000) : 0;
        $creditAmount = !$isDebit ? $this->faker->numberBetween(100000, 10000000) : 0;
        
        // Set transaction date within the last year
        $transactionDate = $this->faker->dateTimeBetween('-1 year', 'now');
        
        // Transaction types
        $types = ['income', 'expense', 'transfer', 'investment', 'loan', 'repayment'];
        
        // Get a CostListInvoice if available
        $costListInvoice = null;
        if ($invoice) {
            $costListInvoice = CostListInvoice::where('invoice_id', $invoice->id)->inRandomOrder()->first();
        }
        
        return [
            'description' => $this->faker->sentence(),
            'cash_reference_id' => $cashReference ? $cashReference->id : CashReference::factory(),
            'mou_id' => $invoice ? $invoice->mou_id : MoU::inRandomOrder()->first()?->id ?? MoU::factory(),
            'coa_id' => $coa ? $coa->id : Coa::factory(),
            'invoice_id' => $invoice ? $invoice->id : Invoice::factory(),
            'cost_list_invoice_id' => $costListInvoice?->id, // Might be null
            'type' => $this->faker->randomElement($types),
            'debit_amount' => $debitAmount,
            'credit_amount' => $creditAmount,
            'transaction_date' => $transactionDate,
        ];
    }
    
    /**
     * Define a state for income transactions.
     */
    public function income()
    {
        return $this->state(function (array $attributes) {
            return [
                'debit_amount' => $this->faker->numberBetween(100000, 10000000),
                'credit_amount' => 0,
                'type' => 'income',
            ];
        });
    }
    
    /**
     * Define a state for expense transactions.
     */
    public function expense()
    {
        return $this->state(function (array $attributes) {
            return [
                'debit_amount' => 0,
                'credit_amount' => $this->faker->numberBetween(100000, 5000000),
                'type' => 'expense',
            ];
        });
    }
} 