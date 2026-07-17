<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NeracaLajurPiutangController extends Controller
{
    public function exportDetailJP(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);

        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        // Delegate to the existing Livewire page class export method for detail JP
        $page = new \App\Filament\Resources\CashReportResource\Pages\NeracaLajurPiutang();
        $page->month = $month;
        $page->year  = $year;
        $page->exportDetailJurnalPendapatan();
    }

    public function exportNeraca(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);

        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        // Delegate to the existing Livewire page class export method for Neraca Lajur
        $page = new \App\Filament\Resources\CashReportResource\Pages\NeracaLajurPiutang();
        $page->month = $month;
        $page->year  = $year;
        $page->exportToExcel();
    }
}
