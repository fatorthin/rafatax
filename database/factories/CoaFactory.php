<?php

namespace Database\Factories;

use App\Models\Coa;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoaFactory extends Factory
{
    protected $model = Coa::class;

    public function definition()
    {
        $types = ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'];
        $codes = ['1000', '2000', '3000', '4000', '5000'];
        
        $typeIndex = array_rand($types);
        
        return [
            'code' => $codes[$typeIndex] . $this->faker->unique()->numerify('###'),
            'name' => $this->faker->words(3, true) . ' ' . $types[$typeIndex],
            'type' => $types[$typeIndex],
        ];
    }
} 