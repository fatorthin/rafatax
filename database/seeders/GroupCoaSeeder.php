<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GroupCoa;
class GroupCoaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groupCoas = [
           [
            'id' => 10,
            'name' => 'Aktiva Lancar',
        ], [
            'id' => 11,
            'name' => 'Investasi Jangka Panjang',
        ], [
            'id' => 12,
            'name' => 'Aktiva Tetap',
        ], [
            'id' => 20,
            'name' => 'Kewajiban',
        ], [
            'id' => 21,
            'name' => 'Kewajiban Jangka Panjang',
        ], [
            'id' => 30,
            'name' => 'Ekuitas',
        ], [
            'id' => 40,
            'name' => 'Pendapatan',
        ], [
            'id' => 50,
            'name' => 'Beban',
        ], [
            'id' => 60,
            'name' => 'Beban Lainnya',
        ], [
            'id' => 70,
            'name' => 'Pendapatan Lainnya',
        ]
        ];

        foreach ($groupCoas as $groupCoa) {
            GroupCoa::create($groupCoa);
        }

    }
}
