<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\CostListInvoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePrintController extends Controller
{
    public function download($id)
    {
        $invoice = Invoice::with(['mou.client'])->findOrFail($id);
        $costLists = CostListInvoice::where('invoice_id', $id)->get();

        // Attempt to generate PDF using barryvdh/laravel-dompdf
        try {
            $pdf = Pdf::loadView('invoices.pdf', [
                'invoice' => $invoice,
                'costLists' => $costLists,
            ])->setPaper('a4', 'portrait');

            $filename = 'invoice-' . ($invoice->invoice_number ?? $invoice->id) . '.pdf';

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            // Fallback: render the view in browser if PDF generation fails
            return view('invoices.pdf', [
                'invoice' => $invoice,
                'costLists' => $costLists,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
