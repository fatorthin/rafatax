<?php

namespace App\Http\Controllers;

use App\Models\MoU;
use App\Models\CostListMou;

class MouPrintViewController extends Controller
{
    public function show($id)
    {
        $mou = MoU::with(['client', 'categoryMou'])->findOrFail($id);
        $costLists = CostListMou::where('mou_id', $id)->get();

        if ($mou->has_custom_builder && !empty($mou->custom_sections)) {
            return view('format-mous.custom-builder', [
                'mou' => $mou,
                'costLists' => $costLists,
                'printMode' => true,
            ]);
        }

        $format = $mou->type === 'pt'
            ? $mou->categoryMou->format_mou_pt
            : $mou->categoryMou->format_mou_kkp;

        if (!$format) {
            abort(404, 'Format print PDF belum diatur untuk kategori MoU ini. Silakan hubungi admin untuk setting kategori.');
        }

        $view = 'format-mous.' . $format;

        return view($view, [
            'mou' => $mou,
            'costLists' => $costLists,
            'printMode' => true,
        ]);
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

    public function previewPdf($id)
    {
        try {
            list($pdf, $filename) = $this->preparePdf($id);
            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            return response($e->getMessage(), 500);
        }
    }

    public function streamPublicPdf($id, $filename = '')
    {
        try {
            $cacheDir = storage_path('app/public/mous');
            if (!file_exists($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }

            $cleanFilename = $filename ? basename($filename) : '';
            $filePath = $cleanFilename ? $cacheDir . '/' . $cleanFilename : null;

            // If file does not exist in cache or is empty, generate and save it
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
            \Illuminate\Support\Facades\Log::error('MouPrintViewController streamPublicPdf error', [
                'id' => $id,
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            return response($e->getMessage(), 500);
        }
    }

    public function preparePdf($id, $withSignature = null)
    {
        $mou = MoU::with(['client', 'categoryMou'])->findOrFail($id);
        $costLists = CostListMou::where('mou_id', $id)->get();

        if ($mou->has_custom_builder && !empty($mou->custom_sections)) {
            $view = 'format-mous.preview.custom-builder';
        } else {
            $format = $mou->type === 'pt'
                ? $mou->categoryMou->format_mou_pt
                : $mou->categoryMou->format_mou_kkp;

            if ($mou->category_mou_id == 8 && request()->has('restitusi_type')) {
                $type = request('restitusi_type');
                if ($type === 'ppn') {
                    $format = 'spk-restitusi-ppn';
                } elseif ($type === 'pph') {
                    $format = 'spk-restitusi-pph-35';
                }
            }

            if (!$format) {
                abort(404, 'Format print PDF belum diatur untuk kategori MoU ini.');
            }

            $view = 'format-mous.preview.' . $format;
        }

        if ($withSignature === null) {
            $withSignature = request('with_signature', 1);
        }

        // Use DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, [
            'mou' => $mou,
            'costLists' => $costLists,
            'printMode' => true,
            'isPdf' => true,
            'withSignature' => $withSignature,
        ])->setPaper('a4', 'portrait')->setOption(['isPhpEnabled' => true, 'compress' => 1]);

        $cleanMouNumber = str_replace(['/', '\\', ' ', ':', '*', '?', '"', '<', '>', '|'], '-', $mou->mou_number ?? (string)$mou->id);
        $cleanCompany = str_replace(['/', '\\', ' ', ':', '*', '?', '"', '<', '>', '|'], '-', $mou->client->company_name ?? 'Client');
        $filename = 'MoU-' . $cleanMouNumber . '-' . $cleanCompany . '.pdf';

        return [$pdf, $filename];
    }

    public function previewPdfTest($id)
    {
        try {
            list($pdf, $filename) = $this->preparePdfTest($id);
            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            return response($e->getMessage(), 500);
        }
    }

    private function preparePdfTest($id)
    {
        $mou = MoU::with(['client', 'categoryMou'])->findOrFail($id);
        $costLists = CostListMou::where('mou_id', $id)->get();

        // FORCE TEST VIEW
        $view = 'format-mous.preview.spk-tahunan-pt-test';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, [
            'mou' => $mou,
            'costLists' => $costLists,
            'printMode' => true,
            'isPdf' => true,
        ])->setPaper('a4', 'portrait')->setOption(['isPhpEnabled' => true]);

        $filename = 'MoU-TEST-' . str_replace(['/', '\\'], '-', $mou->mou_number) . '.pdf';

        return [$pdf, $filename];
    }
}
