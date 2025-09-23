<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\Employee;
use App\Models\ImportError;
use App\Services\EmployeeValidationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessImportRowJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;
    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importJobId,
        public array $importRowIds
    ) {
        $this->onQueue('imports');
    }

    /**
     * Execute the job.
     */
    public function handle(EmployeeValidationService $validator): void
    {
        $importJob = ImportJob::findOrFail($this->importJobId);
        
        try {
            Log::info("Processing import rows", [
                'job_id' => $importJob->job_id,
                'row_count' => count($this->importRowIds)
            ]);
            
            $importRows = ImportRow::whereIn('id', $this->importRowIds)
                ->where('import_job_id', $this->importJobId)
                ->pending()
                ->get();
            
            foreach ($importRows as $importRow) {
                $this->processRow($importRow, $validator);
            }
            
            // Update progress after processing batch
            $importJob->updateProgress();
            
            Log::info("Completed processing import rows", [
                'job_id' => $importJob->job_id,
                'processed_count' => $importRows->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to process import rows", [
                'job_id' => $importJob->job_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process a single import row
     */
    private function processRow(ImportRow $importRow, EmployeeValidationService $validator): void
    {
        try {
            $importRow->markAsProcessing();
            
            $rowData = $importRow->getRowData();
            
            // Validate row data
            $validationResult = $validator->validateRow($rowData);
            
            if (!$validationResult['valid']) {
                $this->handleValidationFailure($importRow, $validationResult['errors']);
                return;
            }
            
            // Check for duplicates within the same import job
            $duplicateCheck = $this->checkForDuplicates($importRow, $rowData);
            if ($duplicateCheck['is_duplicate']) {
                $this->handleDuplicate($importRow, $duplicateCheck['message']);
                return;
            }
            
            // Check for existing employees in database
            $existingEmployee = Employee::findForUpsert(
                $rowData['employee_number'],
                $rowData['email']
            );
            
            if ($existingEmployee) {
                $this->handleExistingEmployee($importRow, $existingEmployee, $rowData);
            } else {
                $this->handleNewEmployee($importRow, $rowData);
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to process import row", [
                'import_row_id' => $importRow->id,
                'row_number' => $importRow->row_number,
                'error' => $e->getMessage()
            ]);
            
            $this->handleSystemError($importRow, $e);
        }
    }

    /**
     * Handle validation failure
     */
    private function handleValidationFailure(ImportRow $importRow, array $errors): void
    {
        $errorMessages = [];
        foreach ($errors as $field => $fieldErrors) {
            $errorMessages = array_merge($errorMessages, $fieldErrors);
        }
        
        $errorMessage = implode(', ', $errorMessages);
        
        $importRow->markAsFailed($errorMessage, $errors);
        
        // Create import error record
        ImportError::createValidationError(
            $importRow->import_job_id,
            $importRow->id,
            $importRow->row_number,
            'VALIDATION_FAILED',
            $errorMessage,
            ['validation_errors' => $errors],
            $importRow->getRowData()
        );
    }

    /**
     * Check for duplicates within the same import job
     */
    private function checkForDuplicates(ImportRow $importRow, array $rowData): array
    {
        $duplicateRow = ImportRow::where('import_job_id', $importRow->import_job_id)
            ->where('id', '!=', $importRow->id)
            ->where('row_number', '<', $importRow->row_number)
            ->whereJsonContains('raw_data->employee_number', $rowData['employee_number'])
            ->orWhereJsonContains('raw_data->email', $rowData['email'])
            ->first();
        
        if ($duplicateRow) {
            return [
                'is_duplicate' => true,
                'message' => "Duplicate employee found in row {$duplicateRow->row_number}. Employee number: {$rowData['employee_number']}, Email: {$rowData['email']}"
            ];
        }
        
        return ['is_duplicate' => false];
    }

    /**
     * Handle duplicate employee
     */
    private function handleDuplicate(ImportRow $importRow, string $message): void
    {
        $importRow->markAsDuplicate($message);
        
        ImportError::createDuplicateError(
            $importRow->import_job_id,
            $importRow->id,
            $importRow->row_number,
            $message,
            $importRow->getRowData()
        );
    }

    /**
     * Handle existing employee (update)
     */
    private function handleExistingEmployee(ImportRow $importRow, Employee $existingEmployee, array $rowData): void
    {
        try {
            DB::transaction(function () use ($importRow, $existingEmployee, $rowData) {
                // Update existing employee
                $existingEmployee->update([
                    'first_name' => $rowData['first_name'],
                    'last_name' => $rowData['last_name'],
                    'department' => $rowData['department'],
                    'salary' => $rowData['salary'],
                    'currency' => $rowData['currency'],
                    'country_code' => $rowData['country_code'],
                    'start_date' => $rowData['start_date'],
                    'last_imported_at' => now(),
                    'last_import_job_id' => $importRow->importJob->job_id,
                ]);
                
                $importRow->markAsSuccess($existingEmployee->id);
            });
            
        } catch (\Exception $e) {
            $this->handleSystemError($importRow, $e);
        }
    }

    /**
     * Handle new employee (create)
     */
    private function handleNewEmployee(ImportRow $importRow, array $rowData): void
    {
        try {
            DB::transaction(function () use ($importRow, $rowData) {
                $employee = Employee::create([
                    'employee_number' => $rowData['employee_number'],
                    'first_name' => $rowData['first_name'],
                    'last_name' => $rowData['last_name'],
                    'email' => $rowData['email'],
                    'department' => $rowData['department'],
                    'salary' => $rowData['salary'],
                    'currency' => $rowData['currency'],
                    'country_code' => $rowData['country_code'],
                    'start_date' => $rowData['start_date'],
                    'last_imported_at' => now(),
                    'last_import_job_id' => $importRow->importJob->job_id,
                ]);
                
                $importRow->markAsSuccess($employee->id);
            });
            
        } catch (\Exception $e) {
            $this->handleSystemError($importRow, $e);
        }
    }

    /**
     * Handle system error
     */
    private function handleSystemError(ImportRow $importRow, \Exception $e): void
    {
        $importRow->markAsFailed($e->getMessage());
        
        ImportError::createSystemError(
            $importRow->import_job_id,
            $importRow->id,
            $importRow->row_number,
            'SYSTEM_ERROR',
            $e->getMessage(),
            ['exception' => get_class($e)],
            $importRow->getRowData()
        );
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessImportRowJob failed", [
            'import_job_id' => $this->importJobId,
            'import_row_ids' => $this->importRowIds,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
