<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mouSample = \App\Models\MoU::with(['cost_lists', 'categoryMou'])->latest()->take(5)->get();

foreach ($mouSample as $mou) {
    echo "MoU ID: " . $mou->id . "\n";
    echo "Category: " . ($mou->categoryMou ? $mou->categoryMou->name : 'N/A') . " (ID: " . $mou->category_mou_id . ")\n";
    echo "Cost Lists:\n";
    foreach ($mou->cost_lists as $cl) {
        $coa = \App\Models\Coa::find($cl->coa_id);
        echo "  - CoA: " . ($coa ? $coa->code . " (" . $coa->name . ")" : 'N/A') . " (ID: " . $cl->coa_id . "), Amount: " . $cl->total_amount . "\n";
    }
    echo "---------------------------\n";
}
