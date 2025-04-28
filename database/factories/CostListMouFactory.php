<?php

namespace Database\Factories;

use App\Models\CostListMou;
use App\Models\MoU;
use App\Models\Coa;
use Illuminate\Database\Eloquent\Factories\Factory;

class CostListMouFactory extends Factory
{
    protected $model = CostListMou::class;

    public function definition()
    {
        return [
            'mou_id' => MoU::factory(),
            'coa_id' => function () {
                // Get random CoA or create one if none exists
                $coa = Coa::inRandomOrder()->first();
                return $coa ? $coa->id : Coa::factory()->create()->id;
            },
            'amount' => $this->faker->numberBetween(1000000, 50000000),
            'description' => $this->faker->sentence(),
        ];
    }
} 