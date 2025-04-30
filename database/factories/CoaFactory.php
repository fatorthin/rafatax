<?php

namespace Database\Factories;

use App\Models\Coa;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoaFactory extends Factory
{
    protected $model = Coa::class;

    public function definition()
    {
        $types = ['pt', 'consultant'];
        $codes = ['1000', '2000'];
        
        $typeIndex = array_rand($types);
        
        return [
            'code' => $codes[$typeIndex] . $this->faker->unique()->numerify('###'),
            'name' => $this->faker->words(3, true) . ' ' . ucfirst($types[$typeIndex]),
            'type' => $types[$typeIndex],
        ];
    }
} 