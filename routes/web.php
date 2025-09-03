<?php


use Illuminate\Support\Facades\Route;
use App\Filament\Resources\MouResource\Pages\CostListMou;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\MouPrintViewController;
use App\Http\Controllers\NeracaLajurController;
use App\Http\Controllers\DaftarAktivaExportController;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect / to public homepage or desired location
// Route::get('/', fn () => view('welcome'));

// Unified login entry: /login -> app panel login
Route::get('/', function () {
    return redirect('/login');
});
Route::get('/login', function () {
    return redirect('/app/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// Redirect root ke app panel jika sudah login
Route::get('/home', function () {
    return redirect('/app');
})->middleware('auth');

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
