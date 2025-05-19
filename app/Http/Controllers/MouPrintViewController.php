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

        return view('format-mous.spk-tahunan-pt', [
            'mou' => $mou,
            'costLists' => $costLists,
            'printMode' => true,
        ]);
    }
} 