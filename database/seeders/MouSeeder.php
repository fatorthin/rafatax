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
        $regularMous = MoU::factory()->count(10)->create();
        
        // Create 5 active MoUs
        $activeMous = MoU::factory()->active()->count(5)->create();
        
        // Create 3 completed MoUs
        $completedMous = MoU::factory()->completed()->count(3)->create();

        // Combine all MoUs
        $allMous = $regularMous->merge($activeMous)->merge($completedMous);

        // Get all existing CoAs or create some if needed
        $coas = Coa::all();
        if ($coas->count() < 5) {
            // Create some CoAs if we don't have enough
            $coas = $coas->merge(Coa::factory()->count(5 - $coas->count())->create());
        }

        // Create cost list items for each MoU
        foreach ($allMous as $mou) {
            // Each MoU will have 2-5 cost list items
            $costItemCount = rand(2, 5);
            
            // Get random CoAs for this MoU
            $mouCoas = $coas->random(min($costItemCount, $coas->count()));
            
            foreach ($mouCoas as $coa) {
                CostListMou::factory()->create([
                    'mou_id' => $mou->id,
                    'coa_id' => $coa->id,
                    'amount' => rand(1000000, 50000000),
                ]);
            }
        }
    }
} 