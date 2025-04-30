<?php

namespace Database\Seeders;

use App\Models\MoU;
use App\Models\CostListMou;
use App\Models\Coa;
use Illuminate\Database\Seeder;

class MouSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 MoUs with random status
        $regularMous = MoU::factory()->count(1000)->create();
        
        // Create 5 active MoUs
        $activeMous = MoU::factory()->active()->count(500)->create();
        
        // Create 3 completed MoUs
        $completedMous = MoU::factory()->completed()->count(300)->create();

        // Combine all MoUs (no need to create cost list mou here)
        // All cost list and invoice logic handled in MoUFactory
    }
} 