<?php

namespace App\Jobs;

use App\Http\Controllers\InvoicePrintController;
use App\Models\Invoice;
use App\Services\WablasService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendInvoiceWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 3;

    /**
     * Backoff seconds between retries
     * @return array<int,int>
     */
    public function backoff(): array
    {
        return [5, 15, 30];
    }

    /** @var int */
    public $timeout = 60; // seconds

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $invoiceId,
        public string $phone
    ) {
        $this->onQueue('whatsapp');
    }

    /**
     * Execute the job.
     */
    public function handle(WablasService $wablasService): void
    {
        $invoice = Invoice::with(['mou.client', 'memo', 'client'])->find($this->invoiceId);

        if (!$invoice) {
            Log::warning('SendInvoiceWhatsapp: Invoice not found', [
                'invoice_id' => $this->invoiceId,
            ]);
            return;
        }

        try {
            Log::info('SendInvoiceWhatsapp: start processing via fast URL delivery', [
                'invoice_id' => $invoice->id,
                'phone' => $this->phone,
            ]);

            // Clean phone number
            $phone = preg_replace('/[^0-9]/', '', $this->phone);
            if (substr($phone, 0, 1) === '0') {
                $phone = '62' . substr($phone, 1);
            } elseif (substr($phone, 0, 2) !== '62') {
                $phone = '62' . $phone;
            }

            // Resolve client name
            $clientName = $this->resolveClientName($invoice) ?: 'Client';

            // Calculate total amount
            $totalAmount = \App\Models\CostListInvoice::where('invoice_id', $invoice->id)->sum('amount');
            $formattedAmount = number_format($totalAmount, 0, ',', '.');

            // Determine Type for Signature and Bank Details
            $type = $invoice->invoice_type
                ?? optional($invoice->mou)->type
                ?? optional($invoice->memo)->tipe_klien;
            $typeNormalized = is_string($type) ? strtolower(trim($type)) : '';
            $isKkp = $typeNormalized === 'kkp';

            $bankDetails = $isKkp
                ? "Bank: BCA\nNo. Rekening: 785-1135-425\nAtas nama: Antin Okfitasari"
                : "Bank: BCA\nNo. Rekening: 785-1260-513\nAtas nama: Aghnia Oasis Konsultindo PT";

            $dueDate = $invoice->due_date
                ? \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d F Y')
                : '-';

            // Build intro caption message
            $invoiceNumber = $invoice->invoice_number ?? $invoice->id;
            $caption = "Yth. Bapak/Ibu {$clientName}\n";
            $caption .= "Kami dari Tim Admin RAFATAX Consulting bersama ini mengirimkan Invoice Tagihan.\n\n";
            $caption .= "No Invoice    : {$invoiceNumber}\n";
            $caption .= "Jumlah          : Rp {$formattedAmount}\n";
            $caption .= "Jatuh Tempo: {$dueDate}\n\n";
            $caption .= "Transfer ke: {$bankDetails}\n\n";
            $caption .= "Note:\n";
            $caption .= "1. Cantumkan nomor invoice di kolom \"catatan\" saat proses transfer.\n";
            $caption .= "2. Konfirmasi pembayaran dengan mengirim bukti transfer ke Nomor Admin (+62 813 5997 6015)\n";
            $caption .= "3. Bayar tepat waktu untuk menghindari penghentian layanan kami.\n\n";
            $caption .= "Mohon dapat menjadi periksa & dijadwalkan pembayarannya\n";
            $caption .= "Terima kasih\n";
            $caption .= "Admin Rafatax Consulting";

            // Clean filename for public URL (no parentheses or special characters)
            $invoiceNumberClean = preg_replace('/[^A-Za-z0-9_\-]/', '-', $invoice->invoice_number ?? (string)$invoice->id);
            $clientClean = preg_replace('/[^A-Za-z0-9_\-]/', '-', $clientName ?: 'Client');
            $filename = 'Invoice-' . $invoiceNumberClean . '-' . $clientClean . '.pdf';

            // Determine public base URL
            $baseUrl = rtrim(config('app.url', 'https://keu.rafatax.id'), '/');
            if (str_contains($baseUrl, 'localhost') || str_contains($baseUrl, '.test') || str_contains($baseUrl, '127.0.0.1')) {
                $baseUrl = 'https://keu.rafatax.id';
            }

            $documentUrl = "{$baseUrl}/invoices/{$invoice->id}/document/{$filename}";

            // 1. Send greeting/intro caption text
            $msgResult = $wablasService->sendMessage($phone, $caption);
            Log::info('SendInvoiceWhatsapp: Text caption sent', [
                'invoice_id' => $invoice->id,
                'phone' => $phone,
                'result' => $msgResult,
            ]);

            // 2. Pre-generate and cache PDF so public route serves instantly
            try {
                $controller = app(InvoicePrintController::class);
                list($pdf, $actualFilename) = $controller->preparePdf($invoice->id);

                $cacheDir = storage_path('app/public/invoices');
                if (!file_exists($cacheDir)) {
                    @mkdir($cacheDir, 0755, true);
                }
                $pdf->save($cacheDir . '/' . $filename);
                Log::info('SendInvoiceWhatsapp: PDF pre-rendered and cached', ['filename' => $filename]);
            } catch (\Throwable $e) {
                Log::warning('SendInvoiceWhatsapp: Pre-render PDF notice (will stream dynamically): ' . $e->getMessage());
            }

            // 3. Send PDF Document via Wablas URL endpoint (/v2/send-document)
            $docResult = $wablasService->sendDocumentUrl($phone, $documentUrl, $filename);
            Log::info('SendInvoiceWhatsapp: Document URL sent to Wablas', [
                'invoice_id' => $invoice->id,
                'phone' => $phone,
                'url' => $documentUrl,
                'result' => $docResult,
            ]);

            $docSuccess = isset($docResult['success']) && $docResult['success'] === true;

            if ($docSuccess) {
                $invoice->update([
                    'is_send_invoice' => true,
                    'send_invoice_date' => now()->toDateString(),
                ]);

                Log::info('SendInvoiceWhatsapp: Invoice successfully sent via WhatsApp URL delivery', [
                    'invoice_id' => $invoice->id,
                    'phone' => $phone,
                ]);
            } else {
                Log::warning('SendInvoiceWhatsapp: Wablas sendDocumentUrl failed, sending fallback direct link message', [
                    'invoice_id' => $invoice->id,
                    'phone' => $phone,
                    'result' => $docResult,
                ]);

                // Fallback: Kirim direct link via text message
                $fallbackMessage = "📄 *INVOICE TAGIHAN*\n\n";
                $fallbackMessage .= "Invoice No: {$invoiceNumber} dapat diunduh melalui tautan berikut:\n\n";
                $fallbackMessage .= "🔗 {$documentUrl}\n\n";
                $fallbackMessage .= "Terima kasih.\nAdmin Rafatax Consulting";

                $wablasService->sendMessage($phone, $fallbackMessage);

                $invoice->update([
                    'is_send_invoice' => true,
                    'send_invoice_date' => now()->toDateString(),
                ]);

                Log::info('SendInvoiceWhatsapp: Invoice sent via fallback direct link', [
                    'invoice_id' => $invoice->id,
                    'phone' => $phone,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SendInvoiceWhatsapp: Exception during job processing', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function resolveClientName(Invoice $invoice): string
    {
        if ($invoice->memo_id && !$invoice->client_id) {
            return $invoice->memo?->nama_klien ?? '';
        }

        if ($invoice->memo_id && $invoice->client_id) {
            return $invoice->client?->company_name ?? '';
        }

        if ($invoice->mou_id && !$invoice->memo_id && !$invoice->client_id) {
            return $invoice->mou?->client?->company_name ?? '';
        }

        return '';
    }
}
