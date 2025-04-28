<?php

namespace Database\Factories;

use App\Models\CashReference;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashReferenceFactory extends Factory
{
    protected $model = CashReference::class;

    public function definition()
    {
        // Generate unique bank names by combining bank type, suffix, and a unique number
        $bankTypes = ['BCA', 'Mandiri', 'BNI', 'BRI', 'BTPN', 'Permata', 'CIMB Niaga'];
        $bankSuffix = ['Corporate', 'Personal', 'Business', 'Savings', 'Operations', 'Investment'];
        
        // Create a unique bank name by using a random bank and suffix, but with a unique number
        return [
            'name' => $this->faker->randomElement($bankTypes) . ' ' . 
                      $this->faker->randomElement($bankSuffix) . ' ' . 
                      $this->faker->unique()->numberBetween(1000, 9999),
            'description' => $this->faker->sentence(),
        ];
    }
} 