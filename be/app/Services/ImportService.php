<?php

namespace App\Services;

use App\Models\ImportJob;
use App\Models\Employee;
use App\Models\ImportError;
use App\Jobs\ProcessBulkImportJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Exception;

class ImportService
{
    private array $expectedHeaders = [
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

    public function initiateImport(UploadedFile $file): ImportJob
    {
        // Validate file
        $this->validateFile($file);

        // Store file
        $filePath = $this->storeFile($file);

        // Parse CSV to get row count
        $totalRows = $this->getCsvRowCount($filePath);

        // Create import job
        $importJob = ImportJob::createFromUpload(
            Str::uuid()->toString(),
            $file->getClientOriginalName(),
            $filePath,
            $file->getSize(),
            $totalRows
        );

        // Dispatch job to queue
        ProcessBulkImportJob::dispatch($importJob->uuid)
            ->onQueue('imports-high-priority');

        return $importJob;
    }

    public function getImportStatus(string $jobId): ?ImportJob
    {
        return ImportJob::where('uuid', $jobId)->first();
    }

    public function getImportJobs(int $limit = 50, int $offset = 0): \Illuminate\Database\Eloquent\Collection
    {
        return ImportJob::orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    public function cancelImport(string $jobId): bool
    {
        $job = ImportJob::where('uuid', $jobId)->first();
        
        if (!$job) {
            return false;
        }

        try {
            $job->cancel();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    public function getImportDetails(string $jobId): ?array
    {
        $job = ImportJob::where('uuid', $jobId)->first();
        
        if (!$job) {
            return null;
        }

        return [
            'job' => $job,
            'employees_count' => $job->employees()->count(),
            'errors_count' => $job->importErrors()->count(),
            'errors_by_type' => $job->importErrors()
                ->selectRaw('error_type, count(*) as count')
                ->groupBy('error_type')
                ->pluck('count', 'error_type')
                ->toArray(),
        ];
    }

    public function getImportErrors(string $jobId, int $limit = 100, int $offset = 0): array
    {
        return ImportError::where('import_job_id', $jobId)
            ->orderBy('row_number')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }

    public function getImportEmployees(string $jobId, int $limit = 100, int $offset = 0): array
    {
        return Employee::where('import_job_id', $jobId)
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }

    public function retryImport(string $jobId): bool
    {
        $job = ImportJob::where('uuid', $jobId)->first();
        if ($job && ($job->status === 'failed' || $job->status === 'cancelled')) {
            $job->update([
                'status' => 'pending',
                'processed_rows' => 0,
                'successful_rows' => 0,
                'failed_rows' => 0,
                'duplicate_rows' => 0,
                'error_message' => null,
                'progress_percentage' => 0,
                'started_at' => null,
                'completed_at' => null,
            ]);
            ProcessBulkImportJob::dispatch($job->uuid)
                ->onQueue('imports-high-priority');
            return true;
        }
        return false;
    }

    public function retryFailedRows(string $jobId): bool
    {
        $job = ImportJob::where('uuid', $jobId)->first();
        if (!$job || !in_array($job->status, ['completed', 'failed'])) {
            return false;
        }

        // Get failed rows and duplicate rows from the original job
        $failedRows = ImportError::where('import_job_id', $jobId)
            ->whereIn('error_type', ['validation', 'duplicate'])
            ->get();

        if ($failedRows->isEmpty()) {
            return false; // No failed or duplicate rows to retry
        }

        // Create a new retry job
        $retryJob = ImportJob::create([
            'uuid' => Str::uuid()->toString(),
            'original_filename' => $job->original_filename . ' (Retry)',
            'file_path' => $job->file_path, // Use the same file
            'file_size' => $job->file_size,
            'total_rows' => $failedRows->count(),
            'status' => 'pending',
        ]);

        // Dispatch retry job with failed rows data
        \App\Jobs\ProcessFailedRowsJob::dispatch($retryJob->uuid, $failedRows->toArray())
            ->onQueue('imports-high-priority');

        return true;
    }

    private function validateFile(UploadedFile $file): void
    {
        $validator = Validator::make(['csv' => $file], [
            'csv' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:' . config('imports.max_file_size', 20 * 1024 * 1024)
            ]
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid file: ' . implode(', ', $validator->errors()->all()));
        }

        // Validate CSV structure
        try {
            $reader = Reader::createFromPath($file->getPathname());
            $reader->setHeaderOffset(0);
            
            $headers = $reader->getHeader();
            if ($headers !== $this->expectedHeaders) {
                throw new \InvalidArgumentException('Invalid CSV headers. Expected: ' . implode(', ', $this->expectedHeaders));
            }

            $rowCount = iterator_count($reader->getRecords());
            if ($rowCount > config('imports.max_rows', 50000)) {
                throw new \InvalidArgumentException('File contains too many rows. Maximum allowed: ' . config('imports.max_rows', 50000));
            }

            if ($rowCount === 0) {
                throw new \InvalidArgumentException('File contains no data rows');
            }

        } catch (Exception $e) {
            throw new \InvalidArgumentException('Invalid CSV format: ' . $e->getMessage());
        }
    }

    private function storeFile(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('imports', $filename, 'public');
        
        return Storage::disk('public')->path($path);
    }

    private function getCsvRowCount(string $filePath): int
    {
        try {
            $reader = Reader::createFromPath($filePath);
            $reader->setHeaderOffset(0);
            return iterator_count($reader->getRecords());
        } catch (Exception $e) {
            return 0;
        }
    }
}
