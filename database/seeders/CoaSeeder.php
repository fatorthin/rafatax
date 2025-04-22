<?php

namespace Database\Seeders;

use App\Models\Coa;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coas = [
            [
                'code' => '101',
                'name' => 'Fee SPT',
                'type' => 'Consultant'
            ],
            [
                'code' => '102',
                'name' => 'Fee Konsultan',
                'type' => 'PT'
            ],
            [
                'code' => '103',
                'name' => 'Fee Pelatihan',
                'type' => 'Consultant'
            ]
        ];

        foreach ($coas as $coa) {
            Coa::create($coa);
        }
    }
}
