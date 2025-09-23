<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\ImportError;
use App\Jobs\ProcessBulkImportJob;
use App\Services\CsvParserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function __construct(
        private CsvParserService $csvParser
    ) {}

    /**
     * Upload and start CSV import
     */
    public function store(StoreImportRequest $request): JsonResponse
    {
        try {
            $file = $request->file('csv_file');
            
            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('imports', $filename, 'local');
            
            // Get the full path to the stored file
            $fullFilePath = Storage::disk('local')->path($filePath);
            
            // Validate CSV file structure
            $validationErrors = $this->csvParser->validateFile($fullFilePath);
            if (!empty($validationErrors)) {
                Storage::disk('local')->delete($filePath);
                return response()->json([
                    'success' => false,
                    'message' => 'CSV file validation failed',
                    'errors' => $validationErrors
                ], 422);
            }
            
            // Get file statistics
            $fileStats = $this->csvParser->getFileStatistics($fullFilePath);
            
            // Create import job
            $importJob = ImportJob::create([
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $fullFilePath,
                'total_rows' => $fileStats['row_count'],
                'metadata' => [
                    'file_size' => $fileStats['file_size'],
                    'headers' => $fileStats['headers'],
                    'estimated_memory_usage' => $fileStats['estimated_memory_usage'],
                    'uploaded_at' => now()->toISOString(),
                ]
            ]);
            
            // Dispatch import job
            ProcessBulkImportJob::dispatch($importJob);
            
            Log::info("CSV import job created", [
                'job_id' => $importJob->job_id,
                'filename' => $file->getClientOriginalName(),
                'total_rows' => $fileStats['row_count']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'CSV file uploaded successfully. Import job started.',
                'data' => [
                    'job_id' => $importJob->job_id,
                    'status' => $importJob->status,
                    'total_rows' => $importJob->total_rows,
                    'created_at' => $importJob->created_at->toISOString(),
                ]
            ], 201);
            
        } catch (\Exception $e) {
            Log::error("Failed to create import job", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create import job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import job status and progress
     */
    public function show(string $jobId): JsonResponse
    {
        try {
            $importJob = ImportJob::where('job_id', $jobId)->firstOrFail();
            
            $progress = $importJob->getCachedProgress();
            $statistics = $importJob->getStatistics();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'job' => $progress,
                    'statistics' => $statistics,
                    'errors' => $this->getErrorSummary($importJob),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import job not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get all import jobs with pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            
            $query = ImportJob::query();
            
            if ($status) {
                $query->where('status', $status);
            }
            
            $importJobs = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            $data = $importJobs->map(function ($job) {
                return [
                    'job_id' => $job->job_id,
                    'original_filename' => $job->original_filename,
                    'status' => $job->status,
                    'total_rows' => $job->total_rows,
                    'processed_rows' => $job->processed_rows,
                    'successful_rows' => $job->successful_rows,
                    'failed_rows' => $job->failed_rows,
                    'duplicate_rows' => $job->duplicate_rows,
                    'progress_percentage' => $job->progress_percentage,
                    'success_rate' => $job->success_rate,
                    'created_at' => $job->created_at->toISOString(),
                    'started_at' => $job->started_at?->toISOString(),
                    'completed_at' => $job->completed_at?->toISOString(),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $importJobs->currentPage(),
                    'last_page' => $importJobs->lastPage(),
                    'per_page' => $importJobs->perPage(),
                    'total' => $importJobs->total(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve import jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import job errors
     */
    public function errors(string $jobId, Request $request): JsonResponse
    {
        try {
            $importJob = ImportJob::where('job_id', $jobId)->firstOrFail();
            
            $perPage = $request->get('per_page', 50);
            $errorType = $request->get('error_type');
            
            $query = $importJob->importErrors();
            
            if ($errorType) {
                $query->where('error_type', $errorType);
            }
            
            $errors = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            $data = $errors->map(function ($error) {
                return [
                    'id' => $error->id,
                    'row_number' => $error->row_number,
                    'error_type' => $error->error_type,
                    'error_code' => $error->error_code,
                    'error_message' => $error->error_message,
                    'error_context' => $error->error_context,
                    'created_at' => $error->created_at->toISOString(),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $errors->currentPage(),
                    'last_page' => $errors->lastPage(),
                    'per_page' => $errors->perPage(),
                    'total' => $errors->total(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve import errors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import job rows with status
     */
    public function rows(string $jobId, Request $request): JsonResponse
    {
        try {
            $importJob = ImportJob::where('job_id', $jobId)->firstOrFail();
            
            $perPage = $request->get('per_page', 50);
            $status = $request->get('status');
            
            $query = $importJob->importRows();
            
            if ($status) {
                $query->where('status', $status);
            }
            
            $rows = $query->orderBy('row_number')
                ->paginate($perPage);
            
            $data = $rows->map(function ($row) {
                return [
                    'id' => $row->id,
                    'row_number' => $row->row_number,
                    'status' => $row->status,
                    'employee_id' => $row->employee_id,
                    'error_message' => $row->error_message,
                    'validation_errors' => $row->validation_errors,
                    'processed_at' => $row->processed_at?->toISOString(),
                    'raw_data' => $row->raw_data,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $rows->currentPage(),
                    'last_page' => $rows->lastPage(),
                    'per_page' => $rows->perPage(),
                    'total' => $rows->total(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve import rows',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an import job
     */
    public function cancel(string $jobId): JsonResponse
    {
        try {
            $importJob = ImportJob::where('job_id', $jobId)->firstOrFail();
            
            if ($importJob->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel completed import job'
                ], 400);
            }
            
            $importJob->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);
            
            $importJob->clearProgressCache();
            
            Log::info("Import job cancelled", ['job_id' => $jobId]);
            
            return response()->json([
                'success' => true,
                'message' => 'Import job cancelled successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel import job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get error summary for import job
     */
    private function getErrorSummary(ImportJob $importJob): array
    {
        $errorStats = ImportError::getErrorStatistics($importJob->id);
        
        return [
            'total_errors' => $errorStats['total_errors'],
            'by_type' => $errorStats['by_type'],
            'by_code' => $errorStats['by_code'],
            'most_common_errors' => array_slice($errorStats['by_code']->toArray(), 0, 5, true),
        ];
    }
}
