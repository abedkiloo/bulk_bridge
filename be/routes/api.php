<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\EmployeeController;

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

// Import Management Routes
Route::prefix('imports')->group(function () {
    Route::post('/', [ImportController::class, 'store'])->name('imports.store');
    Route::get('/', [ImportController::class, 'index'])->name('imports.index');
    Route::get('/{jobId}', [ImportController::class, 'show'])->name('imports.show');
    Route::post('/{jobId}/cancel', [ImportController::class, 'cancel'])->name('imports.cancel');
    Route::get('/{jobId}/errors', [ImportController::class, 'errors'])->name('imports.errors');
    Route::get('/{jobId}/rows', [ImportController::class, 'rows'])->name('imports.rows');
});

// Employee Management Routes
Route::prefix('employees')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/{id}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::delete('/', [EmployeeController::class, 'clearAll'])->name('employees.clear-all');
});

// Health Check Route
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});
