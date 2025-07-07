<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PositionReference;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PositionReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'name' => 'Staff',
                'description' => '-',
            ],
            [
                'name' => 'Leader Operational',
                'description' => '-',
            ],
            [
                'name' => 'Junior Manager',
                'description' => '-',
            ],
            [
                'name' => 'Staff HRD',
                'description' => '-',
            ],
            [
                'name' => 'Coordinator Operational',
                'description' => '-',
            ],
            [
                'name' => 'Plt. Leader Operational',
                'description' => '-',
            ],
        ];

        foreach ($positions as $position) {
            PositionReference::create($position);
        }
    }
}
