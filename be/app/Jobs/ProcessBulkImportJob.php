<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\Employee;
use App\Services\CsvParserService;
use App\Services\EmployeeValidationService;
use App\Services\RedisJobService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\DB;
use League\Csv\Exception;

class ProcessBulkImportJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;
    
    public int $timeout = 1800; // 30 minutes timeout for large files (reduced for better resource management)
    public int $tries = 1; // Don't retry to avoid duplicate issues
    public int $maxExceptions = 1000; // Allow many exceptions before failing the job

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ImportJob $importJob
    ) {
        $this->onQueue('imports');
    }

    /**
     * Execute the job.
     */
    public function handle(CsvParserService $csvParser): void
    {
        try {
            Log::info("Starting bulk import job", ['job_id' => $this->importJob->job_id]);
            
            $this->importJob->markAsStarted();
            
            // Parse CSV and create import rows
            $this->parseAndCreateRows($csvParser);
            
            // Process rows directly instead of batching
            $this->processRowsDirectly();
            
            Log::info("Bulk import job completed successfully", ['job_id' => $this->importJob->job_id]);
            
        } catch (\Exception $e) {
            Log::error("Bulk import job failed", [
                'job_id' => $this->importJob->job_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->importJob->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse CSV file and create import rows
     */
    private function parseAndCreateRows(CsvParserService $csvParser): void
    {
        try {
            $csvData = $csvParser->parseFile($this->importJob->file_path);
            
            if (empty($csvData)) {
                throw new \Exception('CSV file is empty or could not be parsed');
            }
            
            // Validate headers
            Log::info("Headers being validated", ['headers' => $csvData[0]]);
            $this->validateHeaders($csvData[0]);
            
            // Create import rows in optimized chunks for better memory management
            $chunkSize = 1000; // Optimized chunk size for better performance
            $totalRows = count($csvData) - 1; // Exclude header
            
            $this->importJob->update(['total_rows' => $totalRows]);
            
            // Check if import rows already exist for this job
            $existingRowsCount = ImportRow::where('import_job_id', $this->importJob->id)->count();
            if ($existingRowsCount > 0) {
                Log::info("Import rows already exist for job {$this->importJob->id}, skipping creation", [
                    'existing_rows_count' => $existingRowsCount
                ]);
                return;
            }
            
            $rows = array_slice($csvData, 1); // Skip header
            $rowNumber = 1; // Global row counter
            
            // Process in smaller chunks with separate transactions
            foreach (array_chunk($rows, $chunkSize) as $chunkIndex => $chunk) {
                Log::info("Processing chunk {$chunkIndex} of " . ceil(count($rows) / $chunkSize), [
                    'chunk_size' => count($chunk),
                    'start_row' => $rowNumber,
                    'job_id' => $this->importJob->job_id
                ]);
                
                DB::transaction(function () use ($chunk, &$rowNumber) {
                    $importRows = [];
                    
                    foreach ($chunk as $row) {
                        $mappedData = $this->mapRowData($row);
                        $jsonEncodedData = json_encode($mappedData);
                        
                        $importRows[] = [
                            'import_job_id' => $this->importJob->id,
                            'row_number' => $rowNumber,
                            'raw_data' => $jsonEncodedData,
                            'status' => 'pending',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        
                        $rowNumber++;
                    }
                    
                    ImportRow::insert($importRows);
                });
                
                // Memory cleanup between chunks
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
                usleep(50000); // Reduced delay to 0.05 second
            }
            
            Log::info("Created import rows", [
                'job_id' => $this->importJob->job_id,
                'total_rows' => $totalRows
            ]);
            
        } catch (Exception $e) {
            throw new \Exception("CSV parsing failed: " . $e->getMessage());
        }
    }

    /**
     * Validate CSV headers
     */
    private function validateHeaders(array $headers): void
    {
        $requiredHeaders = [
            'employee_number',
            'first_name',
            'last_name',
            'email',
            'department',
            'salary',
            'currency',
            'country_code',
            'start_date'
        ];
        
        $headerMap = array_flip(array_map('strtolower', $headers));
        
        foreach ($requiredHeaders as $required) {
            if (!isset($headerMap[strtolower($required)])) {
                throw new \Exception("Missing required header: {$required}");
            }
        }
    }

    /**
     * Map row data to standardized format
     */
    private function mapRowData(array $row): array
    {
        $headers = [
            'employee_number',
            'first_name',
            'last_name',
            'email',
            'department',
            'salary',
            'currency',
            'country_code',
            'start_date'
        ];
        
        $mappedData = [];
        foreach ($headers as $index => $header) {
            $mappedData[$header] = $row[$index] ?? null;
        }
        
        return $mappedData;
    }

    /**
     * Dispatch batch jobs for processing rows
     */
    private function dispatchBatchJobs(): void
    {
        $batchSize = 100; // Process 100 rows per job
        
        $importRows = $this->importJob->importRows()
            ->pending()
            ->orderBy('row_number')
            ->get();
        
        $jobs = [];
        
        foreach ($importRows->chunk($batchSize) as $chunk) {
            $jobs[] = new ProcessImportRowJob($this->importJob->id, $chunk->pluck('id')->toArray());
        }
        
        if (!empty($jobs)) {
            Bus::batch($jobs)
                ->name("Import Job {$this->importJob->job_id}")
                ->onQueue('imports')
                ->then(function (Batch $batch) {
                    Log::info("Import batch completed", ['job_id' => $this->importJob->job_id]);
                    $this->importJob->markAsCompleted();
                })
                ->catch(function (Batch $batch, \Throwable $e) {
                    Log::error("Import batch failed", [
                        'job_id' => $this->importJob->job_id,
                        'error' => $e->getMessage()
                    ]);
                    $this->importJob->markAsFailed($e->getMessage());
                })
                ->finally(function (Batch $batch) {
                    // Update final progress
                    $this->importJob->updateProgress();
                })
                ->dispatch();
        } else {
            $this->importJob->markAsCompleted();
        }
    }

    /**
     * Process rows in chunks for better memory management
     */
    private function processRowsDirectly(): void
    {
        $chunkSize = 200; // Increased chunk size for better performance
        $successfulRows = 0;
        $failedRows = 0;
        $totalProcessed = 0;
        $skippedRows = 0;
        
        // Get total count for progress tracking (ALL rows, not just pending)
        $totalRows = ImportRow::where('import_job_id', $this->importJob->id)->count();
        
        // Check if there are any rows to process
        if ($totalRows === 0) {
            Log::info("No rows to process for job", ['job_id' => $this->importJob->job_id]);
            $this->importJob->markAsCompleted();
            return;
        }
        
        Log::info("Starting row processing", [
            'job_id' => $this->importJob->job_id,
            'total_rows' => $totalRows
        ]);
        
        // Process ALL rows in chunks (including already processed ones)
        $chunkIndex = 0;
        ImportRow::where('import_job_id', $this->importJob->id)
            ->orderBy('row_number')
            ->chunk($chunkSize, function ($importRows) use (&$successfulRows, &$failedRows, &$totalProcessed, &$skippedRows, $totalRows, &$chunkIndex) {
                $chunkIndex++;
                $chunkSuccessful = 0;
                $chunkFailed = 0;
                $chunkSkipped = 0;
                
                Log::info("Starting chunk {$chunkIndex}", [
                    'job_id' => $this->importJob->job_id,
                    'chunk_size' => count($importRows),
                    'rows_range' => $importRows->first()->row_number . '-' . $importRows->last()->row_number
                ]);
                
                foreach ($importRows as $importRow) {
                    // Skip if already processed successfully
                    if ($importRow->status === 'success') {
                        $successfulRows++;
                        $skippedRows++;
                        $totalProcessed++;
                        $chunkSkipped++;
                        continue;
                    }
                    
                    // Skip if already processed and failed (but count it)
                    if ($importRow->status === 'failed') {
                        $failedRows++;
                        $skippedRows++;
                        $totalProcessed++;
                        $chunkSkipped++;
                        continue;
                    }
                    
                    // Process pending rows
                    try {
                        $this->processImportRow($importRow);
                        $successfulRows++;
                        $chunkSuccessful++;
                    } catch (\Exception $e) {
                        $failedRows++;
                        $chunkFailed++;
                        
                        // Mark the row as failed with detailed error
                        $importRow->update([
                            'status' => 'failed',
                            'validation_errors' => ['error' => $e->getMessage()],
                            'processed_at' => now(),
                        ]);
                    }
                    
                    $totalProcessed++;
                }
                
                // Update progress after each chunk
                $this->importJob->update([
                    'processed_rows' => $totalProcessed,
                    'successful_rows' => $successfulRows,
                    'failed_rows' => $failedRows,
                ]);
                
                // Publish progress update to Redis (optional)
                try {
                    $redisJobService = app(RedisJobService::class);
                    $redisJobService->publishJobUpdate($this->importJob->job_id);
                } catch (\Exception $e) {
                    Log::warning("Failed to publish Redis update", [
                        'job_id' => $this->importJob->job_id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                Log::info("Completed chunk {$chunkIndex}", [
                    'job_id' => $this->importJob->job_id,
                    'chunk_processed' => $chunkSuccessful + $chunkFailed + $chunkSkipped,
                    'chunk_successful' => $chunkSuccessful,
                    'chunk_failed' => $chunkFailed,
                    'chunk_skipped' => $chunkSkipped,
                    'total_processed' => $totalProcessed,
                    'total_successful' => $successfulRows,
                    'total_failed' => $failedRows,
                    'progress' => round(($totalProcessed / $totalRows) * 100, 2) . '%'
                ]);
                
                // Memory cleanup and small delay between chunks
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles(); // Force garbage collection
                }
                usleep(10000); // Reduced delay to 0.01 second for better performance
            });
        
        // Final update
        $this->importJob->update([
            'processed_rows' => $totalProcessed,
            'successful_rows' => $successfulRows,
            'failed_rows' => $failedRows,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        // Publish completion to Redis (optional)
        try {
            $redisJobService = app(RedisJobService::class);
            $redisJobService->publishJobCompletion($this->importJob->job_id, 'completed');
        } catch (\Exception $e) {
            Log::warning("Failed to publish Redis completion", [
                'job_id' => $this->importJob->job_id,
                'error' => $e->getMessage()
            ]);
        }
        
        Log::info("Row processing completed", [
            'job_id' => $this->importJob->job_id,
            'total_processed' => $totalProcessed,
            'successful_rows' => $successfulRows,
            'failed_rows' => $failedRows
        ]);
    }
    
    /**
     * Process a single import row
     */
    private function processImportRow(ImportRow $importRow): void
    {
        $rawData = $importRow->raw_data;
        
        // Validate the data
        $validator = app(EmployeeValidationService::class);
        $validationResult = $validator->validateRow($rawData);
        
        if (!$validationResult['valid']) {
            // Flatten validation errors for display
            $errorMessages = [];
            foreach ($validationResult['errors'] as $field => $fieldErrors) {
                if (is_array($fieldErrors)) {
                    $errorMessages[] = $field . ': ' . implode(', ', $fieldErrors);
                } else {
                    $errorMessages[] = $field . ': ' . $fieldErrors;
                }
            }
            
            $importRow->update([
                'status' => 'failed',
                'validation_errors' => $validationResult['errors'],
                'processed_at' => now(),
            ]);
            throw new \Exception('Validation failed: ' . implode('; ', $errorMessages));
        }
        
        // Create employee
        $employee = Employee::create($rawData);
        
        $importRow->update([
            'status' => 'success',
            'employee_id' => $employee->id,
            'processed_at' => now(),
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessBulkImportJob failed", [
            'job_id' => $this->importJob->job_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        $this->importJob->markAsFailed($exception->getMessage());
    }
}
