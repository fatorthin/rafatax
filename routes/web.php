<?php


use Illuminate\Support\Facades\Route;
use App\Filament\Resources\MouResource\Pages\CostListMou;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\MouPrintViewController;
use App\Http\Controllers\NeracaLajurController;
use App\Http\Controllers\DaftarAktivaExportController;

Route::get('/', function () {
    return view('homepage');
});

Route::get('/test-mou', function () {
    return view('format-mous.spk-tahunan-pt');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('activity-logs', ActivityLogController::class)->only(['index', 'show']);
    Route::get('activity-logs/filter', [ActivityLogController::class, 'filter'])->name('activity-logs.filter');
    
    // Neraca Lajur Export & Save
    Route::get('/neraca-lajur/export', [NeracaLajurController::class, 'export'])->name('neraca-lajur.export');
    Route::get('/neraca-lajur/save-cutoff', [NeracaLajurController::class, 'saveCutOff'])->name('neraca-lajur.save-cutoff');
});

Route::get('/mou/{id}/print-view', [MouPrintViewController::class, 'show'])->name('mou.print.view');
Route::get('/daftar-aktiva/export/{bulan}/{tahun}', [DaftarAktivaExportController::class, 'export'])->name('daftar-aktiva.export');
