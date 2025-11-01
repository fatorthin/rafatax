<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\StaffController;
use App\Http\Controllers\API\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth endpoints (issue/revoke tokens)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Staff API Resource Routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('staff', StaffController::class);
});

// Alternative: Jika ingin menggunakan authentication
// Route::middleware('auth:sanctum')->group(function () {
//     Route::apiResource('staff', StaffController::class);
// });
