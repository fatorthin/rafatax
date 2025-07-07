<?php

namespace Database\Seeders;

use App\Models\JournalBookReference;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JournalBookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $journalBooks = [
            ['name' => 'Jurnal Umum', 'description' => 'Buku Jurnal untuk mencatat transaksi umum'],
            ['name' => 'Jurnal Adjustment (AJE)', 'description' => '-'],
        ];

        foreach ($journalBooks as $journalBook) {
            JournalBookReference::create($journalBook);
        }
    }
}
