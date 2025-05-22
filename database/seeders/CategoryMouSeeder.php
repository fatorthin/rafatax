<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CategoryMou;

class CategoryMouSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryMous = [
           [
            'name' => 'SPT Perusahaan',
        ], [
            'name' => 'SPT Perorangan',
        ], [
            'name' => 'Bulanan Perusahaan',
        ], [
            'name' => 'Bulanan Perorangan',
        ], [
            'name' => 'SP2DK',
        ], [
            'name' => 'Pembetulan',
        ], [
            'name' => 'Pemerikasaan',
        ], [
            'name' => 'Restitusi',
        ], [
            'name' => 'Keberatan',
        ], [
            'name' => 'Konsultasi',
        ], [
            'name' => 'Pembukuan',
        ], [
            'name' => 'Pelatihan',
        ], [
            'name' => 'Lainnya',
        ]
            
        ];

        foreach ($categoryMous as $categoryMou) {
            CategoryMou::create($categoryMou);
        }
    }
}
