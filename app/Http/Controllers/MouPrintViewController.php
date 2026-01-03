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
}
