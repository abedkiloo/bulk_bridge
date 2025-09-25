<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Models\ImportError;
use App\Services\EmployeeProcessingService;
use App\Services\ProgressTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFailedRowsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private string $jobId,
        private array $failedRows
    ) {}

    public function handle(
        EmployeeProcessingService $employeeProcessor,
        ProgressTrackingService $progressTracker
    ): void {
        $job = ImportJob::where('uuid', $this->jobId)->first();
        
        if (!$job || $job->isCompleted()) {
            return; // Idempotency check
        }

        $job->startProcessing();
        $progressTracker->updateProgress($job);

        try {
            $this->processFailedRows($job, $employeeProcessor, $progressTracker);
            $job->complete();
        } catch (\Throwable $e) {
            Log::error('Failed rows retry job failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $job->fail($e->getMessage());
            throw $e;
        } finally {
            $progressTracker->updateProgress($job);
        }
    }

    private function processFailedRows(
        ImportJob $job,
        EmployeeProcessingService $employeeProcessor,
        ProgressTrackingService $progressTracker
    ): void {
        $batchSize = config('imports.batch_size', 500); // Smaller batches for more frequent updates
        $processed = 0;
        $successful = 0;
        $failed = 0;
        $duplicates = 0;
        $totalRows = count($this->failedRows);

        Log::info("Starting to process {$totalRows} failed rows in batches of {$batchSize}");

        // Process failed rows in batches
        $batches = array_chunk($this->failedRows, $batchSize);
        $batchCount = 0;
        
        foreach ($batches as $batch) {
            $batchCount++;
            $batchData = [];
            foreach ($batch as $error) {
                $batchData[] = [
                    'row' => $error['row_number'],
                    'data' => $error['raw_data']
                ];
            }

            Log::info("Processing batch {$batchCount}/" . count($batches) . " with " . count($batch) . " rows");

            $results = $employeeProcessor->processBatch($job->uuid, $batchData);
            $processed += $results['processed'];
            $successful += $results['successful'];
            $failed += $results['failed'];
            $duplicates += $results['duplicates'];

            // Update job progress
            $job->updateProgress($processed, $successful, $failed, $duplicates);
            
            // Send progress update to Redis for real-time tracking
            $progressTracker->updateProgress($job);
            
            Log::info("Batch {$batchCount} completed. Progress: {$processed}/{$totalRows} processed, {$successful} successful, {$failed} failed, {$duplicates} duplicates");
            
            // Small delay to allow for real-time updates
            usleep(100000); // 100ms delay
        }
        
        Log::info("Completed processing all failed rows. Final stats: {$processed} processed, {$successful} successful, {$failed} failed, {$duplicates} duplicates");
    }
}
