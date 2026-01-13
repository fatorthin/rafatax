<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\CostListInvoice;

echo "Attempting to create CostListInvoice with null mou_id...\n";

try {
    // Attempt to create a dummy record (rollback later or use transaction)
    // We need a valid invoice_id and coa_id.
    // Fetch random invoice and coa
    $invoice = \App\Models\Invoice::first();
    $coa = \App\Models\Coa::first();

    if (!$invoice || !$coa) {
        die("No invoice or CoA found to test with.\n");
    }

    \Illuminate\Support\Facades\DB::beginTransaction();

    $item = CostListInvoice::create([
        'invoice_id' => $invoice->id,
        'mou_id' => null,
        'coa_id' => $coa->id,
        'description' => 'Test Item',
        'amount' => 1000,
    ]);

    echo "Success! Item created with ID: " . $item->id . "\n";

    \Illuminate\Support\Facades\DB::rollBack();
    echo "Transaction rolled back.\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
