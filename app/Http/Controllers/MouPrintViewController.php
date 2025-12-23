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

        if ($mou->type === 'pt') {
            $view = 'format-mous.' . $mou->categoryMou->format_mou_pt;
        } else {
            $view = 'format-mous.' . $mou->categoryMou->format_mou_kkp;
        }

        return view($view, [
            'mou' => $mou,
            'costLists' => $costLists,
            'printMode' => true,
        ]);
    }
}
