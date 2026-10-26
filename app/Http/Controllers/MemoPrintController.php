<?php

namespace App\Http\Controllers;

use App\Models\Memo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class MemoPrintController extends Controller
{
    public function preparePdf($id): array
    {
        $memo = Memo::findOrFail($id);

        $viewData = [
            'memo' => $memo,
        ];

        $pdf = Pdf::loadView('memos.pdf-preview', $viewData)
            ->setPaper('a4', 'portrait')
            ->setOption(['compress' => 1]);

        $memoNumberClean = preg_replace('/[^A-Za-z0-9_\-]/', '-', $memo->no_memo ?? (string)$memo->id);
        $clientClean = preg_replace('/[^A-Za-z0-9_\-]/', '-', $memo->nama_klien ?: 'Client');
        $filename = 'Memo-' . $memoNumberClean . '-' . $clientClean . '.pdf';

        return [$pdf, $filename];
    }

    public function previewPdf($id)
    {
        try {
            list($pdf, $filename) = $this->preparePdf($id);
            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            return response($e->getMessage(), 500);
        }
    }

    public function downloadPdf($id)
    {
        try {
            list($pdf, $filename) = $this->preparePdf($id);
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return response($e->getMessage(), 500);
        }
    }

    public function streamPublicPdf($id, $filename = '')
    {
        try {
            $cacheDir = storage_path('app/public/memos');
            if (!file_exists($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }

            $cleanFilename = $filename ? basename($filename) : '';
            $filePath = $cleanFilename ? $cacheDir . '/' . $cleanFilename : null;

            if (!$filePath || !file_exists($filePath) || filesize($filePath) === 0) {
                list($pdf, $actualFilename) = $this->preparePdf($id);
                $targetFilename = $cleanFilename ?: $actualFilename;
                $filePath = $cacheDir . '/' . $targetFilename;
                $cleanFilename = $targetFilename;
                $pdf->save($filePath);
            }

            $disposition = request()->has('download') ? 'attachment' : 'inline';

            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => (string) filesize($filePath),
                'Content-Disposition' => "{$disposition}; filename=\"{$cleanFilename}\"",
                'Cache-Control' => 'public, max-age=86400',
            ]);
        } catch (\Throwable $e) {
            Log::error('MemoPrintController streamPublicPdf error: ' . $e->getMessage());
            return response($e->getMessage(), 500);
        }
    }
}
