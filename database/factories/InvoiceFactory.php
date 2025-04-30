<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\MoU;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition()
    {
        $invoiceDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $dueDate = Carbon::parse($invoiceDate)->addDays(30);

        return [
            'mou_id' => MoU::factory(),
            'invoice_number' => 'INV-' . $this->faker->unique()->numerify('######'),
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'invoice_status' => $this->faker->randomElement(['unpaid', 'paid', 'overdue', 'draft', 'cancelled']),
        ];
    }

    /**
     * Define a state for paid invoices.
     */
    public function paid()
    {
        return $this->state(function (array $attributes) {
            return [
                'invoice_status' => 'paid',
            ];
        });
    }

    /**
     * Define a state for overdue invoices.
     */
    public function overdue()
    {
        return $this->state(function (array $attributes) {
            $invoiceDate = $this->faker->dateTimeBetween('-3 months', '-1 month');
            $dueDate = Carbon::parse($invoiceDate)->addDays(15);
            
            return [
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'invoice_status' => 'overdue',
            ];
        });
    }
} 