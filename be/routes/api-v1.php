<?php

use App\Http\Controllers\Api\V1\ImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes v1
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    // Import routes
    Route::prefix('imports')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('api.v1.imports.index');
        Route::post('/', [ImportController::class, 'store'])->name('api.v1.imports.store');
        Route::get('/{jobId}', [ImportController::class, 'show'])->name('api.v1.imports.show');
        Route::get('/{jobId}/details', [ImportController::class, 'details'])->name('api.v1.imports.details');
        Route::get('/{jobId}/status', [ImportController::class, 'status'])->name('api.v1.imports.status');
        Route::get('/{jobId}/errors', [ImportController::class, 'errors'])->name('api.v1.imports.errors');
        Route::get('/{jobId}/employees', [ImportController::class, 'employees'])->name('api.v1.imports.employees');
        Route::post('/{jobId}/cancel', [ImportController::class, 'cancel'])->name('api.v1.imports.cancel');
        Route::post('/{jobId}/retry', [ImportController::class, 'retry'])->name('api.v1.imports.retry');
        Route::post('/{jobId}/retry-failed', [ImportController::class, 'retryFailed'])->name('api.v1.imports.retry-failed');
    });
});
