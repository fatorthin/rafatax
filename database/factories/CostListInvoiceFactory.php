<?php

namespace Database\Factories;

use App\Models\CostListInvoice;
use App\Models\MoU;
use App\Models\Invoice;
use App\Models\Coa;
use Illuminate\Database\Eloquent\Factories\Factory;

class CostListInvoiceFactory extends Factory
{
    protected $model = CostListInvoice::class;

    public function definition()
    {
        $invoice = Invoice::inRandomOrder()->first();
        
        return [
            'invoice_id' => $invoice ? $invoice->id : Invoice::factory(),
            'mou_id' => function (array $attributes) use ($invoice) {
                // If we have an invoice, use its MoU id, otherwise create one
                if ($invoice) {
                    return $invoice->mou_id;
                }
                return MoU::factory();
            },
            'coa_id' => function () {
                // Get random CoA or create one if none exists
                $coa = Coa::inRandomOrder()->first();
                return $coa ? $coa->id : Coa::factory()->create()->id;
            },
            'amount' => $this->faker->numberBetween(500000, 20000000),
            'description' => $this->faker->sentence(),
        ];
    }
} 