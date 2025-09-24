<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\ImportError;
use App\Jobs\ProcessBulkImportJob;
use App\Services\CsvParserService;
use App\Services\RedisJobService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function __construct(
        private CsvParserService $csvParser,
        private ?RedisJobService $redisJobService = null
    ) {}

    /**
     * Upload and start CSV import
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file uploaded'
                ], 400);
            }
            
            // Validate file type
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file upload'
                ], 400);
            }
            
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
                'job' => [
                    'job_id' => $importJob->job_id,
                    'filename' => $importJob->filename,
                    'original_filename' => $importJob->original_filename,
                    'status' => $importJob->status,
                    'total_rows' => $importJob->total_rows,
                    'processed_rows' => $importJob->processed_rows,
                    'successful_rows' => $importJob->successful_rows,
                    'failed_rows' => $importJob->failed_rows,
                    'file_size' => $fileStats['file_size'],
                    'created_at' => $importJob->created_at->toISOString(),
                    'started_at' => $importJob->started_at?->toISOString(),
                    'completed_at' => $importJob->completed_at?->toISOString(),
                    'error_message' => $importJob->error_message,
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
            
            return response()->json([
                'success' => true,
                'data' => [
                    'job_id' => $importJob->job_id,
                    'filename' => $importJob->filename,
                    'original_filename' => $importJob->original_filename,
                    'status' => $importJob->status,
                    'total_rows' => $importJob->total_rows,
                    'processed_rows' => $importJob->processed_rows,
                    'successful_rows' => $importJob->successful_rows,
                    'failed_rows' => $importJob->failed_rows,
                    'duplicate_rows' => $importJob->duplicate_rows,
                    'progress_percentage' => $importJob->progress_percentage,
                    'success_rate' => $importJob->success_rate,
                    'file_size' => $importJob->metadata['file_size'] ?? null,
                    'created_at' => $importJob->created_at->toISOString(),
                    'started_at' => $importJob->started_at?->toISOString(),
                    'completed_at' => $importJob->completed_at?->toISOString(),
                    'error_message' => $importJob->error_message,
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
     * Get detailed import job information
     */
    public function details(string $jobId): JsonResponse
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
     * Retry a failed import job
     */
    public function retry(string $jobId): JsonResponse
    {
        try {
            $importJob = ImportJob::where('job_id', $jobId)->firstOrFail();
            
            if ($importJob->status !== 'failed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only failed jobs can be retried'
                ], 400);
            }
            
            // Reset job status and clear previous results
            $importJob->update([
                'status' => 'pending',
                'processed_rows' => 0,
                'successful_rows' => 0,
                'failed_rows' => 0,
                'duplicate_rows' => 0,
                'error_message' => null,
                'started_at' => null,
                'completed_at' => null,
            ]);
            
            // Clear related data
            $importJob->importRows()->delete();
            $importJob->importErrors()->delete();
            $importJob->clearProgressCache();
            
            // Dispatch the job again
            ProcessBulkImportJob::dispatch($importJob);
            
            Log::info("Import job retried", ['job_id' => $jobId]);
            
            return response()->json([
                'success' => true,
                'message' => 'Import job retried successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry import job',
                'error' => $e->getMessage()
            ], 500);
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
     * Get job status via Redis polling
     */
    public function status(string $jobId): JsonResponse
    {
        try {
            // Try Redis first, fall back to database if Redis is unavailable
            $jobData = null;
            if ($this->redisJobService) {
                try {
                    $jobData = $this->redisJobService->getJobStatus($jobId);
                } catch (\Exception $redisError) {
                    Log::warning('Redis unavailable, falling back to database', [
                        'job_id' => $jobId,
                        'error' => $redisError->getMessage()
                    ]);
                }
            }
            
            // If Redis failed or returned no data, get from database
            if (!$jobData) {
                $importJob = ImportJob::where('job_id', $jobId)->first();
                if (!$importJob) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Job not found'
                    ], 404);
                }
                
                $jobData = [
                    'job_id' => $importJob->job_id,
                    'filename' => $importJob->filename,
                    'original_filename' => $importJob->original_filename,
                    'status' => $importJob->status,
                    'total_rows' => $importJob->total_rows,
                    'processed_rows' => $importJob->processed_rows,
                    'successful_rows' => $importJob->successful_rows,
                    'failed_rows' => $importJob->failed_rows,
                    'duplicate_rows' => $importJob->duplicate_rows,
                    'progress_percentage' => $importJob->progress_percentage,
                    'success_rate' => $importJob->success_rate,
                    'file_size' => $importJob->metadata['file_size'] ?? null,
                    'created_at' => $importJob->created_at->toISOString(),
                    'started_at' => $importJob->started_at?->toISOString(),
                    'completed_at' => $importJob->completed_at?->toISOString(),
                    'error_message' => $importJob->error_message,
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $jobData,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting job status', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving job status'
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
