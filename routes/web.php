<?php


use Illuminate\Support\Facades\Route;
use App\Filament\Resources\MouResource\Pages\CostListMou;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\MouPrintViewController;

Route::get('/', function () {
    return view('homepage');
});

Route::get('/test-mou', function () {
    return view('format-mous.spk-tahunan-pt');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('activity-logs', ActivityLogController::class)->only(['index', 'show']);
    Route::get('activity-logs/filter', [ActivityLogController::class, 'filter'])->name('activity-logs.filter');
});

Route::get('/mou/{id}/print-view', [MouPrintViewController::class, 'show'])->name('mou.print.view');
