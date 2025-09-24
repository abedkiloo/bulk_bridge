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
Route::prefix('import')->group(function () {
    Route::post('/upload', [ImportController::class, 'store'])->name('import.upload');
    Route::get('/jobs', [ImportController::class, 'index'])->name('import.jobs');
    Route::get('/job/{jobId}', [ImportController::class, 'show'])->name('import.job.show');
    Route::get('/job/{jobId}/details', [ImportController::class, 'details'])->name('import.job.details');
    Route::post('/job/{jobId}/cancel', [ImportController::class, 'cancel'])->name('import.job.cancel');
    Route::post('/job/{jobId}/retry', [ImportController::class, 'retry'])->name('import.job.retry');
    Route::post('/job/{jobId}/dispatch', [ImportController::class, 'dispatch'])->name('import.job.dispatch');
    Route::get('/job/{jobId}/errors', [ImportController::class, 'errors'])->name('import.job.errors');
    Route::get('/job/{jobId}/rows', [ImportController::class, 'rows'])->name('import.job.rows');
    Route::get('/job/{jobId}/status', [ImportController::class, 'status'])->name('import.job.status');
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
