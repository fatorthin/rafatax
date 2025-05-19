<?php

namespace Database\Factories;

use App\Models\Coa;
use App\Models\MoU;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\CategoryMou;
use App\Models\CostListMou;
use App\Models\CostListInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class MoUFactory extends Factory
{
    protected $model = MoU::class;

    public function definition()
    {
        return [
            'mou_number' => 'MOU-' . $this->faker->unique()->numerify('####'),
            'description' => $this->faker->sentence(),
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+2 years'),
            'client_id' => Client::factory(),
            'status' => $this->faker->randomElement(['approved', 'unapproved']),
            'type' => $this->faker->randomElement(['pt', 'kkp']),
            'category_mou_id' => CategoryMou::factory(),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterCreating(function (MoU $mou) {
            // Get random CoAs or create some if needed
            $coas = Coa::inRandomOrder()->take(rand(2, 5))->get();
            if ($coas->count() < 2) {
                $coas = $coas->merge(Coa::factory()->count(2 - $coas->count())->create());
            }

            // Create cost list items for the MoU
            $totalMouAmount = 0;
            foreach ($coas as $coa) {
                // Generate a random amount between 1,000,000 and 50,000,000
                $amount = $this->faker->numberBetween(1000000, 50000000);
                $totalMouAmount += $amount;

                CostListMou::factory()->create([
                    'mou_id' => $mou->id,
                    'coa_id' => $coa->id,
                    'amount' => $amount,
                    'description' => $this->faker->sentence(),
                ]);
            }

            // Create invoices with cost list items that total less than the MoU amount
            $invoiceCount = rand(1, 3);
            $totalInvoiceAmount = 0;
            $remainingAmount = $totalMouAmount;

            for ($i = 0; $i < $invoiceCount; $i++) {
                $invoice = Invoice::factory()->create([
                    'mou_id' => $mou->id,
                    'invoice_status' => $this->faker->randomElement(['unpaid', 'paid', 'overdue', 'draft', 'cancelled']),
                ]);

                // Calculate maximum possible amount for this invoice
                $maxInvoiceAmount = $remainingAmount;
                if ($i < $invoiceCount - 1) {
                    // For all invoices except the last one, use a portion of remaining amount
                    $maxInvoiceAmount = $this->faker->numberBetween(
                        $remainingAmount / ($invoiceCount - $i) / 2, // Minimum 50% of equal share
                        $remainingAmount / ($invoiceCount - $i) * 1.5 // Maximum 150% of equal share
                    );
                }

                // Create cost list items for the invoice
                $invoiceAmount = $maxInvoiceAmount;
                $totalInvoiceAmount += $invoiceAmount;
                $remainingAmount -= $invoiceAmount;

                CostListInvoice::factory()->create([
                    'invoice_id' => $invoice->id,
                    'mou_id' => $mou->id,
                    'coa_id' => $coas->random()->id,
                    'amount' => $invoiceAmount,
                    'description' => $this->faker->sentence(),
                ]);
            }
        });
    }

    /**
     * Define a state for active MoUs.
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    /**
     * Define a state for completed MoUs.
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }
} 