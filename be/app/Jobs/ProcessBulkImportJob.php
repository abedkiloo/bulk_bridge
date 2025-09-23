<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Services\CsvParserService;
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

    public int $timeout = 3600; // 1 hour
    public int $tries = 3;
    public int $maxExceptions = 3;

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
            
            // Dispatch batch jobs for processing rows
            $this->dispatchBatchJobs();
            
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
            
            // Create import rows in batches for memory efficiency
            $batchSize = 1000;
            $totalRows = count($csvData) - 1; // Exclude header
            
            $this->importJob->update(['total_rows' => $totalRows]);
            
            DB::transaction(function () use ($csvData, $batchSize) {
                $rows = array_slice($csvData, 1); // Skip header
                
                foreach (array_chunk($rows, $batchSize) as $chunk) {
                    $importRows = [];
                    
                    foreach ($chunk as $index => $row) {
                        $rowNumber = $index + 1;
                        
                        $mappedData = $this->mapRowData($row);
                        $jsonEncodedData = json_encode($mappedData);
                        
                        // Debug logging
                        Log::info("Row data debug", [
                            'row_number' => $rowNumber,
                            'mapped_data_type' => gettype($mappedData),
                            'json_encoded_type' => gettype($jsonEncodedData),
                            'json_encoded_value' => $jsonEncodedData,
                            'is_array_mapped' => is_array($mappedData),
                            'is_string_encoded' => is_string($jsonEncodedData)
                        ]);
                        
                        $importRows[] = [
                            'import_job_id' => $this->importJob->id,
                            'row_number' => $rowNumber,
                            'raw_data' => $jsonEncodedData,
                            'status' => 'pending',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    
                    ImportRow::insert($importRows);
                }
            });
            
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
