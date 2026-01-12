<?php

namespace App\Http\Controllers;

use App\Models\MoU;
use App\Models\CostListMou;
use Illuminate\Http\Request;

class MouPrintViewController extends Controller
{
    public function show($id)
    {
        $mou = MoU::with(['client', 'categoryMou'])->findOrFail($id);
        $costLists = CostListMou::where('mou_id', $id)->get();

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

    private function preparePdf($id)
    {
        $mou = MoU::with(['client', 'categoryMou'])->findOrFail($id);
        $costLists = CostListMou::where('mou_id', $id)->get();

        $format = $mou->type === 'pt'
            ? $mou->categoryMou->format_mou_pt
            : $mou->categoryMou->format_mou_kkp;

        if (!$format) {
            abort(404, 'Format print PDF belum diatur untuk kategori MoU ini.');
        }

        $view = 'format-mous.' . $format;
        // Use DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, [
            'mou' => $mou,
            'costLists' => $costLists,
            'printMode' => true,
        ])->setPaper('a4', 'portrait');

        $filename = 'MoU-' . str_replace(['/', '\\'], '-', $mou->mou_number) . '.pdf';

        return [$pdf, $filename];
    }
}
