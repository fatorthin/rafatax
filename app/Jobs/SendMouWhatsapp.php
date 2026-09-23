<?php

namespace App\Jobs;

use App\Http\Controllers\MouPrintViewController;
use App\Models\MoU;
use App\Services\WablasService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMouWhatsapp implements ShouldQueue
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
        public int $mouId,
        public string $phone,
        public bool $withSignature = true
    ) {
        $this->onQueue('whatsapp');
    }

    /**
     * Execute the job.
     */
    public function handle(WablasService $wablasService): void
    {
        $mou = MoU::with(['client', 'categoryMou'])->find($this->mouId);

        if (!$mou) {
            Log::warning('SendMouWhatsapp: MoU not found', [
                'mou_id' => $this->mouId,
            ]);
            return;
        }

        try {
            Log::info('SendMouWhatsapp: start processing via fast URL delivery', [
                'mou_id' => $mou->id,
                'phone' => $this->phone,
                'with_signature' => $this->withSignature,
            ]);

            // Clean phone number
            $phone = preg_replace('/[^0-9]/', '', $this->phone);
            if (substr($phone, 0, 1) === '0') {
                $phone = '62' . substr($phone, 1);
            } elseif (substr($phone, 0, 2) !== '62') {
                $phone = '62' . $phone;
            }

            // Build caption message
            $ownerName = $mou->client?->owner_name ?? 'Bapak/Ibu';
            $mouNumber = $mou->mou_number ?? '-';
            $categoryName = $mou->categoryMou?->name ?? '-';
            $companyName = $mou->client?->company_name ?? '-';

            $caption = "Yth. Bapak/Ibu {$ownerName}\n";
            $caption .= "Kami dari Tim Admin RAFATAX Consulting bersama ini mengirimkan draft MOU Kerjasama untuk tahun 2026.\n";
            $caption .= "Mohon dapat dipelajari dan ditandatangani sebagi bukti persetujuan.\n";
            $caption .= "No Mou \t\t: {$mouNumber}\n";
            $caption .= "Jenis Pekerjaan \t: {$categoryName} {$companyName}\n";
            $caption .= "Ketentuan:\n";
            $caption .= "- MoU wajib di Tandatangani dan di kirim kembali kepada kami Max 7 Hari setelah pesan ini di kirim.\n\n";
            $caption .= "Terima kasih\n";
            $caption .= "Admin Rafatax Consulting";

            // Clean filename for public URL
            $mouNumberClean = str_replace(['/', '\\', ' ', ':', '*', '?', '"', '<', '>', '|'], '-', $mou->mou_number ?? (string)$mou->id);
            $clientClean = str_replace(['/', '\\', ' ', ':', '*', '?', '"', '<', '>', '|'], '-', $mou->client?->company_name ?? 'Client');
            $filename = 'MoU-' . $mouNumberClean . '-' . $clientClean . '.pdf';

            // Determine public base URL
            $baseUrl = rtrim(config('app.url', 'https://keu.rafatax.id'), '/');
            if (str_contains($baseUrl, 'localhost') || str_contains($baseUrl, '.test') || str_contains($baseUrl, '127.0.0.1')) {
                $baseUrl = 'https://keu.rafatax.id';
            }

            $sigParam = $this->withSignature ? '1' : '0';
            $documentUrl = "{$baseUrl}/mou/{$mou->id}/document/{$filename}?with_signature={$sigParam}";

            // 1. Send greeting/intro caption text
            $msgResult = $wablasService->sendMessage($phone, $caption);
            Log::info('SendMouWhatsapp: Text caption sent', [
                'mou_id' => $mou->id,
                'phone' => $phone,
                'result' => $msgResult,
            ]);

            // 2. Pre-generate and cache PDF so public route serves instantly (10ms)
            try {
                $controller = app(MouPrintViewController::class);
                list($pdf, $actualFilename) = $controller->preparePdf($mou->id, $this->withSignature ? 1 : 0);

                $cacheDir = storage_path('app/public/mous');
                if (!file_exists($cacheDir)) {
                    @mkdir($cacheDir, 0755, true);
                }
                $pdf->save($cacheDir . '/' . $filename);
                Log::info('SendMouWhatsapp: PDF pre-rendered and cached', ['filename' => $filename]);
            } catch (\Throwable $e) {
                Log::warning('SendMouWhatsapp: Pre-render PDF notice (will stream dynamically): ' . $e->getMessage());
            }

            // 3. Send PDF Document via Wablas URL endpoint (/v2/send-document)
            $docResult = $wablasService->sendDocumentUrl($phone, $documentUrl, $filename);
            Log::info('SendMouWhatsapp: Document URL sent to Wablas', [
                'mou_id' => $mou->id,
                'phone' => $phone,
                'url' => $documentUrl,
                'result' => $docResult,
            ]);

            $docSuccess = isset($docResult['success']) && $docResult['success'] === true;

            if ($docSuccess) {
                $mou->update([
                    'is_send_mou' => true,
                    'send_mou_date' => now()->toDateString(),
                ]);

                Log::info('SendMouWhatsapp: MoU successfully sent via WhatsApp URL delivery', [
                    'mou_id' => $mou->id,
                    'phone' => $phone,
                ]);
            } else {
                Log::warning('SendMouWhatsapp: Wablas sendDocumentUrl failed, sending fallback direct link message', [
                    'mou_id' => $mou->id,
                    'phone' => $phone,
                    'result' => $docResult,
                ]);

                // Fallback: Kirim direct link via text message
                $fallbackMessage = "📄 *DRAFT MOU KERJASAMA*\n\n";
                $fallbackMessage .= "Dokumen MoU No: {$mouNumber} dapat diunduh melalui tautan berikut:\n\n";
                $fallbackMessage .= "🔗 {$documentUrl}\n\n";
                $fallbackMessage .= "Mohon dipelajari dan ditandatangani sebagai bukti persetujuan.\n";
                $fallbackMessage .= "Terima kasih.\nAdmin Rafatax Consulting";

                $wablasService->sendMessage($phone, $fallbackMessage);

                $mou->update([
                    'is_send_mou' => true,
                    'send_mou_date' => now()->toDateString(),
                ]);

                Log::info('SendMouWhatsapp: MoU sent via fallback direct link', [
                    'mou_id' => $mou->id,
                    'phone' => $phone,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SendMouWhatsapp: Exception during job processing', [
                'mou_id' => $mou->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
