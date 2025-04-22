<?php

namespace Database\Seeders;

use App\Models\CashReference;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CashReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $references = [
            ['name' => 'BCA PT'],
            ['name' => 'BCA Baru'],
            ['name' => 'BCA Lama'],
            ['name' => 'BCA Bpk'],
            ['name' => 'Mandiri'],
            ['name' => 'Kas Besar'],
            ['name' => 'Kas Kecil'],
        ];

        foreach ($references as $reference) {
            CashReference::create($reference);
        }
    }
}
