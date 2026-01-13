<?php

namespace App\Http\Controllers;

use App\Models\Memo;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class MemoPrintController extends Controller
{
    public function previewPdf($id)
    {
        $memo = Memo::findOrFail($id);

        $viewData = [
            'memo' => $memo,
        ];

        $pdf = Pdf::loadView('memos.pdf-preview', $viewData)
            ->setPaper('a4', 'portrait')
            ->setOption(['compress' => 1]);

        return $pdf->stream('memo-' . str_replace('/', '-', $memo->no_memo) . '.pdf');
    }
}
