<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Invoice;
use App\Models\CostListInvoice;

$invoiceNumber = 'INV/001/KKP/LN/I/2026';
$invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

if ($invoice) {
    echo "Invoice found: ID " . $invoice->id . "\n";
    echo "Invoice Amount (DB): " . ($invoice->amount ?? 'NULL') . "\n";

    $items = CostListInvoice::where('invoice_id', $invoice->id)->get();
    echo "Cost List Items Count: " . $items->count() . "\n";
    foreach ($items as $item) {
        echo " - Item ID: {$item->id}, Amount: {$item->amount}, Desc: {$item->description}\n";
    }
} else {
    echo "Invoice not found for number: $invoiceNumber\n";
}
