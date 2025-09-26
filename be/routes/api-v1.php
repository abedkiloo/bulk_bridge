<?php

use App\Http\Controllers\Api\V1\ImportController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\SystemController;
use App\Http\Controllers\Api\V1\StatisticsController;
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
    
    // Import Management Routes
    Route::prefix('imports')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('api.v1.imports.index');
        Route::post('/', [ImportController::class, 'store'])->name('api.v1.imports.store');
        Route::get('/{jobId}', [ImportController::class, 'show'])->name('api.v1.imports.show');
        Route::get('/{jobId}/details', [ImportController::class, 'details'])->name('api.v1.imports.details');
        Route::get('/{jobId}/status', [ImportController::class, 'status'])->name('api.v1.imports.status');
        Route::get('/{jobId}/status/redis', [ImportController::class, 'statusFromRedis'])->name('api.v1.imports.status.redis');
        Route::get('/{jobId}/errors', [ImportController::class, 'errors'])->name('api.v1.imports.errors');
        Route::get('/{jobId}/employees', [ImportController::class, 'employees'])->name('api.v1.imports.employees');
        Route::post('/{jobId}/cancel', [ImportController::class, 'cancel'])->name('api.v1.imports.cancel');
        Route::post('/{jobId}/retry', [ImportController::class, 'retry'])->name('api.v1.imports.retry');
        Route::post('/{jobId}/retry-failed', [ImportController::class, 'retryFailed'])->name('api.v1.imports.retry-failed');
    });

    // Employee Management Routes
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('api.v1.employees.index');
        Route::get('/{id}', [EmployeeController::class, 'show'])->name('api.v1.employees.show');
        Route::put('/{id}', [EmployeeController::class, 'update'])->name('api.v1.employees.update');
        Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('api.v1.employees.destroy');
        Route::get('/departments', [EmployeeController::class, 'departments'])->name('api.v1.employees.departments');
        Route::get('/statistics', [EmployeeController::class, 'statistics'])->name('api.v1.employees.statistics');
        Route::delete('/clear-all', [EmployeeController::class, 'clearAll'])->name('api.v1.employees.clear-all');
        Route::post('/bulk-update', [EmployeeController::class, 'bulkUpdate'])->name('api.v1.employees.bulk-update');
    });

    // System Management Routes
    Route::prefix('system')->group(function () {
        Route::get('/health', [SystemController::class, 'health'])->name('api.v1.system.health');
        Route::get('/status', [SystemController::class, 'status'])->name('api.v1.system.status');
        Route::get('/metrics', [SystemController::class, 'metrics'])->name('api.v1.system.metrics');
        
        // Redis Management
        Route::prefix('redis')->group(function () {
            Route::get('/keys', [SystemController::class, 'redisKeys'])->name('api.v1.system.redis.keys');
            Route::delete('/clear', [SystemController::class, 'clearRedis'])->name('api.v1.system.redis.clear');
        });
        
        // Queue Management
        Route::prefix('queue')->group(function () {
            Route::get('/status', [SystemController::class, 'queueStatus'])->name('api.v1.system.queue.status');
            Route::post('/retry-failed', [SystemController::class, 'retryFailedJobs'])->name('api.v1.system.queue.retry-failed');
        });
        
        // Logs
        Route::get('/logs', [SystemController::class, 'logs'])->name('api.v1.system.logs');
    });

    // Statistics and Analytics Routes
    Route::prefix('statistics')->group(function () {
        Route::get('/overview', [StatisticsController::class, 'overview'])->name('api.v1.statistics.overview');
        Route::get('/imports', [StatisticsController::class, 'imports'])->name('api.v1.statistics.imports');
        Route::get('/employees', [StatisticsController::class, 'employees'])->name('api.v1.statistics.employees');
        Route::get('/errors', [StatisticsController::class, 'errors'])->name('api.v1.statistics.errors');
        Route::get('/performance', [StatisticsController::class, 'performance'])->name('api.v1.statistics.performance');
        Route::get('/trends', [StatisticsController::class, 'trends'])->name('api.v1.statistics.trends');
    });
});
