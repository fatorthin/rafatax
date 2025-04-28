<?php

namespace Database\Factories;

use App\Models\MoU;
use App\Models\Client;
use App\Models\CashReference;
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
            'status' => $this->faker->randomElement(['draft', 'active', 'completed', 'cancelled']),
            'type' => $this->faker->randomElement(['service', 'product', 'consultation']),
        ];
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