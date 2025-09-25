<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\ImportError;
use App\Models\ImportJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EmployeeProcessingService
{
    public function processBatch(string $jobId, array $batch): array
    {
        return DB::transaction(function () use ($jobId, $batch) {
            $processed = 0;
            $successful = 0;
            $failed = 0;
            $duplicates = 0;

            foreach ($batch as $item) {
                $processed++;
                $rowNumber = $item['row'];
                $data = $item['data'];

                // Validate the row
                $validationResult = $this->validateEmployeeData($data);
                if (!$validationResult['valid']) {
                    $failed++;
                    ImportError::createValidationError($jobId, $rowNumber, $data, $validationResult['errors']);
                    continue;
                }

                // Check for duplicates
                if ($this->isDuplicate($data)) {
                    $duplicates++;
                    ImportError::createDuplicateError($jobId, $rowNumber, $data);
                    continue;
                }

                // Create employee
                try {
                    Employee::create([
                        'employee_number' => $data['employee_number'],
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $data['email'],
                        'department' => $data['department'],
                        'salary' => $data['salary'],
                        'currency' => $data['currency'],
                        'country_code' => $data['country_code'],
                        'start_date' => $data['start_date'],
                        'import_job_id' => $jobId,
                    ]);
                    $successful++;
                } catch (\Exception $e) {
                    $failed++;
                    ImportError::createSystemError($jobId, $rowNumber, $data, $e->getMessage());
                    Log::warning('Failed to create employee', [
                        'job_id' => $jobId,
                        'row_number' => $rowNumber,
                        'data' => $data,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return [
                'processed' => $processed,
                'successful' => $successful,
                'failed' => $failed,
                'duplicates' => $duplicates,
            ];
        });
    }

    private function validateEmployeeData(array $data): array
    {
        $validator = Validator::make($data, Employee::getValidationRules());
        
        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray()
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    private function isDuplicate(array $data): bool
    {
        return Employee::where('employee_number', $data['employee_number'])
            ->orWhere('email', $data['email'])
            ->exists();
    }

}
