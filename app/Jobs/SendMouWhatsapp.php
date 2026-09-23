<?php

namespace App\Jobs;

use App\Models\CostListMou;
use App\Models\MoU;
use App\Services\WablasService;
use Barryvdh\DomPDF\Facade\Pdf;
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
    public $timeout = 180; // seconds

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

        $tempPath = null;

        try {
            Log::info('SendMouWhatsapp: start processing', [
                'mou_id' => $mou->id,
                'phone' => $this->phone,
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

            // Query cost lists
            $costLists = CostListMou::query()->where('mou_id', '=', $mou->id)->get();

            if ($mou->has_custom_builder && !empty($mou->custom_sections)) {
                $view = 'format-mous.preview.custom-builder';
            } else {
                $format = $mou->type === 'pt'
                    ? $mou->categoryMou?->format_mou_pt
                    : $mou->categoryMou?->format_mou_kkp;

                if (!$format) {
                    Log::error('SendMouWhatsapp: Format print PDF belum diatur', [
                        'mou_id' => $mou->id,
                        'category_id' => $mou->category_mou_id,
                    ]);
                    return;
                }

                $view = 'format-mous.preview.' . $format;
            }

            $pdf = Pdf::loadView($view, [
                'mou' => $mou,
                'costLists' => $costLists,
                'printMode' => true,
                'isPdf' => true,
                'withSignature' => $this->withSignature ? 1 : 0,
            ])->setPaper('a4', 'portrait')->setOption(['isPhpEnabled' => true, 'compress' => 1]);

            // Save to temporary file
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $mouNumberClean = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $mou->mou_number);
            $filename = 'MoU-' . $mouNumberClean . '.pdf';
            $tempPath = $tempDir . '/' . $filename;

            $pdf->save($tempPath);

            // Send text caption first
            $wablasService->sendMessage($phone, $caption);

            // Send PDF document
            $sendResult = $wablasService->sendDocument($phone, $tempPath);

            if (isset($sendResult['status']) && $sendResult['status']) {
                $mou->update([
                    'is_send_mou' => true,
                    'send_mou_date' => now()->toDateString(),
                ]);

                Log::info('SendMouWhatsapp: MoU successfully sent via WhatsApp', [
                    'mou_id' => $mou->id,
                    'phone' => $phone,
                ]);
            } else {
                Log::warning('SendMouWhatsapp: Failed sending MoU PDF via WhatsApp', [
                    'mou_id' => $mou->id,
                    'phone' => $phone,
                    'result' => $sendResult,
                ]);

                throw new \RuntimeException('Wablas gagal mengirim dokumen PDF: ' . ($sendResult['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            Log::error('SendMouWhatsapp: Exception during job processing', [
                'mou_id' => $mou->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }
}
