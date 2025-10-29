<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\NeracaLajurController;
use App\Http\Controllers\MouPrintViewController;
use App\Http\Controllers\ExportPayrollController;
use App\Http\Controllers\PayrollWhatsAppController;
use App\Http\Controllers\ExportAttendanceController;
use App\Http\Controllers\DaftarAktivaExportController;
use App\Http\Controllers\CashReferenceMonthController;
use App\Filament\Resources\MouResource\Pages\CostListMou;

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
Route::get('/exports/attendance/monthly', [ExportAttendanceController::class, 'exportMonthly'])
    ->name('exports.attendance.monthly');

Route::get('/exports/payroll/{payroll}/excel', [ExportPayrollController::class, 'exportDetailExcel'])
    ->name('exports.payroll.excel');

Route::get('/exports/payroll-detail/{detail}/payslip', [ExportPayrollController::class, 'payslipPdf'])
    ->name('exports.payroll.payslip');

Route::post('/payroll-detail/{detail}/send-whatsapp', [PayrollWhatsAppController::class, 'sendPayslip'])
    ->name('payroll.send-whatsapp')
    ->middleware('auth');

Route::post('/payroll-detail/{detail}/send-whatsapp-pdf', [PayrollWhatsAppController::class, 'sendPayslipWithPdf'])
    ->name('payroll.send-whatsapp-pdf')
    ->middleware('auth');

// Route untuk panel App
Route::post('/app/payroll-detail/{detail}/send-wablas', [PayrollWhatsAppController::class, 'sendPayslipWithPdf'])
    ->name('app.payroll.send-wablas')
    ->middleware('auth');

// Cash Reference Month Detail - Custom View
Route::get('/cash-reference/{id}/month-detail', [CashReferenceMonthController::class, 'show'])
    ->name('cash-reference.month-detail')
    ->middleware('auth');

Route::post('/cash-reference/{id}/transaction/store', [CashReferenceMonthController::class, 'store'])
    ->name('cash-reference.transaction.store')
    ->middleware('auth');

Route::get('/cash-reference/transaction/{transactionId}/edit', [CashReferenceMonthController::class, 'edit'])
    ->name('cash-reference.transaction.edit')
    ->middleware('auth');

Route::put('/cash-reference/transaction/{transactionId}/update', [CashReferenceMonthController::class, 'update'])
    ->name('cash-reference.transaction.update')
    ->middleware('auth');

Route::delete('/cash-reference/transaction/{transactionId}/delete', [CashReferenceMonthController::class, 'delete'])
    ->name('cash-reference.transaction.delete')
    ->middleware('auth');
