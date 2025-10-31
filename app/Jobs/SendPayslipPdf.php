<?php

namespace App\Jobs;

use App\Models\PayrollDetail;
use App\Services\WablasService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPayslipPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public $timeout = 120; // seconds per job

    /**
     * Create a new job instance.
     */
    public function __construct(public int $payrollDetailId)
    {
        $this->onQueue('whatsapp');
    }

    /**
     * Execute the job.
     */
    public function handle(WablasService $wablas): void
    {
        $detail = PayrollDetail::with('staff')->find($this->payrollDetailId);

        if (!$detail) {
            Log::warning('SendPayslipPdf: PayrollDetail not found', [
                'detail_id' => $this->payrollDetailId,
            ]);
            return;
        }

        try {
            Log::info('SendPayslipPdf: start', [
                'detail_id' => $detail->id,
                'staff' => optional($detail->staff)->name,
            ]);

            // Gunakan controller yang sudah menangani generate PDF + kirim WA + fallback
            $controller = new \App\Http\Controllers\PayrollWhatsAppController($wablas);
            $result = $controller->sendPayslipWithPdf($detail);

            // Normalisasi hasil
            $ok = false;
            if (is_array($result)) {
                $ok = ($result['success'] ?? false) === true;
            } elseif (is_object($result) && method_exists($result, 'getData')) {
                $data = $result->getData(true);
                $ok = ($data['success'] ?? false) === true;
            }

            if ($ok) {
                Log::info('SendPayslipPdf: success', [
                    'detail_id' => $detail->id,
                ]);
            } else {
                Log::warning('SendPayslipPdf: failed', [
                    'detail_id' => $detail->id,
                    'result' => $result,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SendPayslipPdf: exception', [
                'detail_id' => $detail->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // let queue handle retries
        }
    }
}
