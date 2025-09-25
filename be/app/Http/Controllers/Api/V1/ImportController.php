<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ImportService;
use App\Http\Requests\ImportCsvRequest;
use App\Http\Resources\ImportJobResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(
        private ImportService $importService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/imports",
     *     summary="Initiate CSV import",
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data")),
     *     @OA\Response(response=202, description="Import initiated")
     * )
     */
    public function store(ImportCsvRequest $request): JsonResponse
    {
        try {
            $importJob = $this->importService->initiateImport($request->file('csv'));

            return response()->json([
                'message' => 'Import initiated successfully',
                'data' => new ImportJobResource($importJob),
                'status_url' => route('api.v1.imports.show', $importJob->uuid)
            ], 202);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Invalid file',
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed',
                'error' => 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/imports",
     *     summary="Get all import jobs",
     *     @OA\Response(response=200, description="Import jobs list")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);

        $jobs = $this->importService->getImportJobs($limit, $offset);

        return response()->json([
            'data' => ImportJobResource::collection($jobs),
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => count($jobs)
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/imports/{jobId}",
     *     summary="Get import status",
     *     @OA\Response(response=200, description="Import status")
     * )
     */
    public function show(string $jobId): JsonResponse
    {
        $job = $this->importService->getImportStatus($jobId);
        
        if (!$job) {
            return response()->json([
                'message' => 'Import job not found'
            ], 404);
        }

        return response()->json([
            'data' => new ImportJobResource($job)
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/imports/{jobId}/details",
     *     summary="Get detailed import information",
     *     @OA\Response(response=200, description="Import details")
     * )
     */
    public function details(string $jobId): JsonResponse
    {
        $details = $this->importService->getImportDetails($jobId);
        
        if (!$details) {
            return response()->json([
                'message' => 'Import job not found'
            ], 404);
        }

        return response()->json([
            'data' => $details
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/imports/{jobId}/errors",
     *     summary="Get import errors",
     *     @OA\Response(response=200, description="Import errors")
     * )
     */
    public function errors(string $jobId, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 100);
        $offset = $request->get('offset', 0);

        $errors = $this->importService->getImportErrors($jobId, $limit, $offset);

        return response()->json([
            'data' => $errors,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => count($errors)
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/imports/{jobId}/employees",
     *     summary="Get imported employees",
     *     @OA\Response(response=200, description="Imported employees")
     * )
     */
    public function employees(string $jobId, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 100);
        $offset = $request->get('offset', 0);

        $employees = $this->importService->getImportEmployees($jobId, $limit, $offset);

        return response()->json([
            'data' => $employees,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => count($employees)
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/imports/{jobId}/cancel",
     *     summary="Cancel import job",
     *     @OA\Response(response=200, description="Import cancelled")
     * )
     */
    public function cancel(string $jobId): JsonResponse
    {
        $success = $this->importService->cancelImport($jobId);
        
        if (!$success) {
            return response()->json([
                'message' => 'Import job not found or cannot be cancelled'
            ], 404);
        }

        return response()->json([
            'message' => 'Import job cancelled successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/imports/{jobId}/retry",
     *     summary="Retry failed import job",
     *     @OA\Response(response=200, description="Import retried")
     * )
     */
    public function retry(string $jobId): JsonResponse
    {
        $success = $this->importService->retryImport($jobId);
        
        if (!$success) {
            return response()->json([
                'message' => 'Import job not found or cannot be retried'
            ], 404);
        }

        return response()->json([
            'message' => 'Import job retried successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/imports/{jobId}/retry-failed",
     *     summary="Retry only failed rows from an import job",
     *     @OA\Response(response=200, description="Failed rows retry initiated")
     * )
     */
    public function retryFailed(string $jobId): JsonResponse
    {
        if ($this->importService->retryFailedRows($jobId)) {
            return response()->json([
                'message' => 'Failed rows retry initiated successfully'
            ]);
        }

        return response()->json([
            'message' => 'No failed rows to retry or job not found'
        ], 404);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/imports/{jobId}/status",
     *     summary="Get import job status",
     *     @OA\Response(response=200, description="Import status")
     * )
     */
    public function status(string $jobId): JsonResponse
    {
        $job = $this->importService->getImportStatus($jobId);
        
        if (!$job) {
            return response()->json([
                'message' => 'Import job not found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'job_id' => $job->uuid,
                'status' => $job->status,
                'progress_percentage' => $job->progress_percentage,
                'processed_rows' => $job->processed_rows,
                'total_rows' => $job->total_rows,
                'successful_rows' => $job->successful_rows,
                'failed_rows' => $job->failed_rows,
                'duplicate_rows' => $job->duplicate_rows,
                'updated_at' => $job->updated_at->toISOString()
            ]
        ]);
    }
}
