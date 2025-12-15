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

        // Choose view based on MoU type (use custom KKP/PT design)
        // Determine invoice type (prioritize Invoice type, fallback to MoU type)
        $type = $invoice->invoice_type ?? optional($invoice->mou)->type;
        $typeNormalized = is_string($type) ? strtolower(trim($type)) : '';

        if ($typeNormalized === 'kkp') {
            $view = 'invoices.pdf-kkp';
            $headerImageFile = 'kop-inovice-kkp.png';
        } elseif ($typeNormalized === 'pt') {
            $view = 'invoices.pdf-pt';
            $headerImageFile = 'kop-invoice-pt.png';
        } else {
            $view = 'invoices.pdf';
            $headerImageFile = null;
        }

        // Convert header image to base64 for PDF embedding
        $headerImageBase64 = '';
        if ($headerImageFile) {
            $headerImagePath = public_path('images/' . $headerImageFile);
            if (file_exists($headerImagePath)) {
                $imageData = file_get_contents($headerImagePath);
                $headerImageBase64 = 'data:image/png;base64,' . base64_encode($imageData);
            }
        }

        // Convert signature image to base64
        $signatureImageBase64 = '';
        $signatureImagePath = public_path('images/spesimen-kasir.png');
        if (file_exists($signatureImagePath)) {
            $signatureData = file_get_contents($signatureImagePath);
            $signatureImageBase64 = 'data:image/png;base64,' . base64_encode($signatureData);
        }

        $viewData = [
            'invoice' => $invoice,
            'costLists' => $costLists,
            'headerImage' => $headerImageBase64,
            'signatureImage' => $signatureImageBase64,
        ];

        // Attempt to generate PDF using barryvdh/laravel-dompdf
        try {
            $pdf = Pdf::loadView($view, $viewData)->setPaper('a4', 'portrait');

            // Clean invoice number to remove invalid filename characters
            $invoiceNumberClean = str_replace(
                ['/', '\\', ':', '*', '?', '"', '<', '>', '|'],
                '-',
                $invoice->invoice_number ?? $invoice->id
            );
            $filename = 'invoice-' . $invoiceNumberClean . '.pdf';

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            // Fallback: render the selected view in browser if PDF generation fails
            $viewData['error'] = $e->getMessage();
            return view($view, $viewData);
        }
    }

    public function previewJpg($id)
    {
        $invoice = Invoice::with(['mou.client'])->findOrFail($id);
        $costLists = CostListInvoice::where('invoice_id', $id)->get();

        // Choose view based on Invoice Type (prioritize Invoice type, fallback to MoU type)
        $type = $invoice->invoice_type ?? optional($invoice->mou)->type;
        $typeNormalized = is_string($type) ? strtolower(trim($type)) : '';

        if ($typeNormalized === 'kkp') {
            $view = 'invoices.pdf-kkp';
            $headerImageFile = 'kop-inovice-kkp.png';
        } elseif ($typeNormalized === 'pt') {
            $view = 'invoices.pdf-pt';
            $headerImageFile = 'kop-invoice-pt.png';
        } else {
            $view = 'invoices.pdf';
            $headerImageFile = null;
        }

        // Convert header image to base64
        $headerImageBase64 = '';
        if ($headerImageFile) {
            $headerImagePath = public_path('images/' . $headerImageFile);
            if (file_exists($headerImagePath)) {
                $imageData = file_get_contents($headerImagePath);
                $headerImageBase64 = 'data:image/png;base64,' . base64_encode($imageData);
            }
        }

        // Convert signature image to base64
        $signatureImageBase64 = '';
        $signatureImagePath = public_path('images/spesimen-kasir.png');
        if (file_exists($signatureImagePath)) {
            $signatureData = file_get_contents($signatureImagePath);
            $signatureImageBase64 = 'data:image/png;base64,' . base64_encode($signatureData);
        }

        $viewData = [
            'invoice' => $invoice,
            'costLists' => $costLists,
            'headerImage' => $headerImageBase64,
            'signatureImage' => $signatureImageBase64,
            'originalView' => $view, // Pass the original view name to include
        ];

        return view('invoices.jpg-preview', $viewData);
    }
}
