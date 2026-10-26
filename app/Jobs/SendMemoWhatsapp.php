<?php

namespace App\Jobs;

use App\Http\Controllers\MemoPrintController;
use App\Models\Memo;
use App\Services\WhatsAppGatewayService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMemoWhatsapp implements ShouldQueue
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
        public int $memoId,
        public string $phone,
        public ?string $recipientName = null
    ) {
        $this->onQueue('whatsapp');
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppGatewayService $whatsAppService): void
    {
        $memo = Memo::find($this->memoId);

        if (!$memo) {
            Log::warning('SendMemoWhatsapp: Memo not found', [
                'memo_id' => $this->memoId,
            ]);
            return;
        }

        try {
            Log::info('SendMemoWhatsapp: start processing via fast URL delivery', [
                'memo_id' => $memo->id,
                'phone' => $this->phone,
            ]);

            // Clean phone number
            $phone = preg_replace('/[^0-9]/', '', $this->phone);
            if (substr($phone, 0, 1) === '0') {
                $phone = '62' . substr($phone, 1);
            } elseif (substr($phone, 0, 2) !== '62') {
                $phone = '62' . $phone;
            }

            // Resolve recipient name
            $recipientName = $this->recipientName;
            if (!$recipientName || $recipientName === 'nama_klien') {
                $recipientName = $memo->nama_klien;
            } elseif ($recipientName === 'instansi_klien') {
                $recipientName = $memo->instansi_klien ?: $memo->nama_klien;
            }

            $totalFeeFormatted = number_format($memo->total_fee ?? 0, 0, ',', '.');
            $tanggalTtd = $memo->tanggal_ttd
                ? \Carbon\Carbon::parse($memo->tanggal_ttd)->locale('id')->translatedFormat('d F Y')
                : '-';

            // Build intro caption message
            $caption = "Yth. Bapak/Ibu {$recipientName}\n";
            if (!empty($memo->instansi_klien) && $recipientName !== $memo->instansi_klien) {
                $caption .= "{$memo->instansi_klien}\n";
            }
            $caption .= "Kami dari Tim Admin RAFATAX Consulting bersama ini mengirimkan Dokumen Memo Kesepakatan Kerja.\n\n";
            $caption .= "No Memo     : {$memo->no_memo}\n";
            $caption .= "Deskripsi   : {$memo->description}\n";
            $caption .= "Total Fee   : Rp {$totalFeeFormatted}\n";
            $caption .= "Tanggal TTD : {$tanggalTtd}\n\n";
            $caption .= "Mohon dapat dipelajari dan ditandatangani sebagai bukti persetujuan.\n\n";
            $caption .= "Terima kasih\n";
            $caption .= "Admin Rafatax Consulting";

            // Clean filename for public URL (no special characters)
            $memoNumberClean = preg_replace('/[^A-Za-z0-9_\-]/', '-', $memo->no_memo ?? (string)$memo->id);
            $clientClean = preg_replace('/[^A-Za-z0-9_\-]/', '-', $recipientName ?: 'Client');
            $filename = 'Memo-' . $memoNumberClean . '-' . $clientClean . '.pdf';

            // Determine public base URL
            $baseUrl = rtrim(config('app.url', 'https://keu.rafatax.id'), '/');
            if (str_contains($baseUrl, 'localhost') || str_contains($baseUrl, '.test') || str_contains($baseUrl, '127.0.0.1')) {
                $baseUrl = 'https://keu.rafatax.id';
            }

            $documentUrl = "{$baseUrl}/memos/{$memo->id}/document/{$filename}";

            // 1. Send greeting/intro caption text
            $msgResult = $whatsAppService->sendMessage($phone, $caption);
            Log::info('SendMemoWhatsapp: Text caption sent', [
                'memo_id' => $memo->id,
                'phone' => $phone,
                'result' => $msgResult,
            ]);

            // 2. Pre-generate and cache PDF so public route serves instantly
            $cachedFilePath = null;
            try {
                $controller = app(MemoPrintController::class);
                list($pdf, $actualFilename) = $controller->preparePdf($memo->id);

                $cacheDir = storage_path('app/public/memos');
                if (!file_exists($cacheDir)) {
                    @mkdir($cacheDir, 0755, true);
                }
                $cachedFilePath = $cacheDir . '/' . $filename;
                $pdf->save($cachedFilePath);
                Log::info('SendMemoWhatsapp: PDF pre-rendered and cached', ['filename' => $filename]);
            } catch (\Throwable $e) {
                Log::warning('SendMemoWhatsapp: Pre-render PDF notice (will stream dynamically): ' . $e->getMessage());
            }

            // 3. Send PDF Document via WhatsApp Gateway URL endpoint
            $docResult = $whatsAppService->sendDocumentUrl($phone, $documentUrl, $filename);
            Log::info('SendMemoWhatsapp: Document URL sent to WhatsApp gateway', [
                'memo_id' => $memo->id,
                'phone' => $phone,
                'url' => $documentUrl,
                'result' => $docResult,
            ]);

            $docSuccess = isset($docResult['success']) && $docResult['success'] === true;

            // If URL send failed but we have local cached file, try sending file directly
            if (!$docSuccess && $cachedFilePath && file_exists($cachedFilePath)) {
                Log::info('SendMemoWhatsapp: sendDocumentUrl failed, attempting direct sendDocument with cached file', [
                    'memo_id' => $memo->id,
                    'file' => $cachedFilePath,
                ]);
                $docResult = $whatsAppService->sendDocument($phone, $cachedFilePath, $filename);
                $docSuccess = isset($docResult['success']) && $docResult['success'] === true;
            }

            if ($docSuccess) {
                Log::info('SendMemoWhatsapp: Memo successfully sent via WhatsApp delivery', [
                    'memo_id' => $memo->id,
                    'phone' => $phone,
                ]);
            } else {
                Log::warning('SendMemoWhatsapp: WhatsApp document send failed, sending fallback direct link message', [
                    'memo_id' => $memo->id,
                    'phone' => $phone,
                    'result' => $docResult,
                ]);

                // Fallback: Kirim direct link via text message
                $fallbackMessage = "📄 *MEMO KESEPAKATAN KERJA*\n\n";
                $fallbackMessage .= "Dokumen Memo No: {$memo->no_memo} dapat diunduh melalui tautan berikut:\n\n";
                $fallbackMessage .= "🔗 {$documentUrl}\n\n";
                $fallbackMessage .= "Mohon dipelajari dan ditandatangani sebagai bukti persetujuan.\n";
                $fallbackMessage .= "Terima kasih.\nAdmin Rafatax Consulting";

                $whatsAppService->sendMessage($phone, $fallbackMessage);

                Log::info('SendMemoWhatsapp: Memo sent via fallback direct link', [
                    'memo_id' => $memo->id,
                    'phone' => $phone,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SendMemoWhatsapp: Exception during job processing', [
                'memo_id' => $memo->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
