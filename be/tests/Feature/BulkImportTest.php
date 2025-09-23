<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\ImportError;
use App\Jobs\ProcessBulkImportJob;
use App\Jobs\ProcessImportRowJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /**
     * Test successful CSV upload and import job creation
     */
    public function test_can_upload_csv_file(): void
    {
        $csvContent = "employee_number,first_name,last_name,email,department,salary,currency,country_code,start_date\n" .
                     "EMP-12345678,John,Doe,john.doe@workmail.co,Engineering,75000,USD,US,2020-01-15\n" .
                     "EMP-87654321,Jane,Smith,jane.smith@company.africa,Finance,65000,EUR,GB,2019-06-01";

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_employees.csv');
        file_put_contents($tempFile, $csvContent);

        $file = new UploadedFile(
            $tempFile,
            'employees.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->postJson('/api/imports', [
            'csv_file' => $file
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'job_id',
                        'status',
                        'total_rows',
                        'created_at'
                    ]
                ]);

        $this->assertDatabaseHas('import_jobs', [
            'original_filename' => 'employees.csv',
            'total_rows' => 2,
            'status' => 'pending'
        ]);

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test CSV file validation
     */
    public function test_validates_csv_file_requirements(): void
    {
        // Test missing file
        $response = $this->postJson('/api/imports', []);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['csv_file']);

        // Test invalid file type
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');
        $response = $this->postJson('/api/imports', [
            'csv_file' => $file
        ]);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['csv_file']);

        // Test file too large
        $file = UploadedFile::fake()->create('large.csv', 25000, 'text/csv');
        $response = $this->postJson('/api/imports', [
            'csv_file' => $file
        ]);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['csv_file']);
    }

    /**
     * Test import job status retrieval
     */
    public function test_can_get_import_job_status(): void
    {
        $importJob = ImportJob::factory()->create([
            'status' => 'processing',
            'total_rows' => 100,
            'processed_rows' => 50,
            'successful_rows' => 45,
            'failed_rows' => 5
        ]);

        $response = $this->getJson("/api/imports/{$importJob->job_id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'job' => [
                            'job_id',
                            'status',
                            'total_rows',
                            'processed_rows',
                            'successful_rows',
                            'failed_rows',
                            'progress_percentage'
                        ],
                        'statistics',
                        'errors'
                    ]
                ]);
    }

    /**
     * Test import job listing
     */
    public function test_can_list_import_jobs(): void
    {
        ImportJob::factory()->count(3)->create();

        $response = $this->getJson('/api/imports');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'job_id',
                            'original_filename',
                            'status',
                            'total_rows',
                            'progress_percentage'
                        ]
                    ],
                    'pagination'
                ]);
    }

    /**
     * Test import job cancellation
     */
    public function test_can_cancel_import_job(): void
    {
        $importJob = ImportJob::factory()->create([
            'status' => 'processing'
        ]);

        $response = $this->postJson("/api/imports/{$importJob->job_id}/cancel");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Import job cancelled successfully'
                ]);

        $this->assertDatabaseHas('import_jobs', [
            'id' => $importJob->id,
            'status' => 'cancelled'
        ]);
    }

    /**
     * Test import errors retrieval
     */
    public function test_can_get_import_errors(): void
    {
        $importJob = ImportJob::factory()->create();
        
        ImportError::factory()->count(3)->create([
            'import_job_id' => $importJob->id
        ]);

        $response = $this->getJson("/api/imports/{$importJob->job_id}/errors");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'row_number',
                            'error_type',
                            'error_code',
                            'error_message'
                        ]
                    ],
                    'pagination'
                ]);
    }

    /**
     * Test import rows retrieval
     */
    public function test_can_get_import_rows(): void
    {
        $importJob = ImportJob::factory()->create();
        
        ImportRow::factory()->count(3)->create([
            'import_job_id' => $importJob->id
        ]);

        $response = $this->getJson("/api/imports/{$importJob->job_id}/rows");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'row_number',
                            'status',
                            'raw_data'
                        ]
                    ],
                    'pagination'
                ]);
    }

    /**
     * Test job queue processing
     */
    public function test_process_bulk_import_job(): void
    {
        Queue::fake();

        $importJob = ImportJob::factory()->create();

        ProcessBulkImportJob::dispatch($importJob);

        Queue::assertPushed(ProcessBulkImportJob::class, function ($job) use ($importJob) {
            return $job->importJob->id === $importJob->id;
        });
    }

    /**
     * Test row processing job
     */
    public function test_process_import_row_job(): void
    {
        Queue::fake();

        $importJob = ImportJob::factory()->create();
        $importRows = ImportRow::factory()->count(5)->create([
            'import_job_id' => $importJob->id
        ]);

        $rowIds = $importRows->pluck('id')->toArray();

        ProcessImportRowJob::dispatch($importJob->id, $rowIds);

        Queue::assertPushed(ProcessImportRowJob::class, function ($job) use ($importJob, $rowIds) {
            return $job->importJobId === $importJob->id && 
                   $job->importRowIds === $rowIds;
        });
    }

    /**
     * Test employee creation through import
     */
    public function test_employee_creation_from_import(): void
    {
        $importJob = ImportJob::factory()->create();
        
        $rowData = [
            'employee_number' => 'EMP-12345678',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@workmail.co',
            'department' => 'Engineering',
            'salary' => 75000,
            'currency' => 'USD',
            'country_code' => 'US',
            'start_date' => '2020-01-15'
        ];

        $importRow = ImportRow::factory()->create([
            'import_job_id' => $importJob->id,
            'raw_data' => $rowData,
            'status' => 'pending'
        ]);

        $employee = Employee::upsertEmployee($rowData, $importJob->job_id);

        $this->assertDatabaseHas('employees', [
            'employee_number' => 'EMP-12345678',
            'email' => 'john.doe@workmail.co',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $this->assertEquals($importJob->job_id, $employee->last_import_job_id);
    }

    /**
     * Test duplicate handling
     */
    public function test_handles_duplicate_employees(): void
    {
        $importJob = ImportJob::factory()->create();
        
        // Create existing employee
        $existingEmployee = Employee::factory()->create([
            'employee_number' => 'EMP-12345678',
            'email' => 'john.doe@workmail.co'
        ]);

        $rowData = [
            'employee_number' => 'EMP-12345678',
            'first_name' => 'John Updated',
            'last_name' => 'Doe Updated',
            'email' => 'john.doe@workmail.co',
            'department' => 'Engineering',
            'salary' => 80000,
            'currency' => 'USD',
            'country_code' => 'US',
            'start_date' => '2020-01-15'
        ];

        $updatedEmployee = Employee::upsertEmployee($rowData, $importJob->job_id);

        $this->assertEquals($existingEmployee->id, $updatedEmployee->id);
        $this->assertEquals('John Updated', $updatedEmployee->first_name);
        $this->assertEquals(80000, $updatedEmployee->salary);
        $this->assertEquals($importJob->job_id, $updatedEmployee->last_import_job_id);
    }

    /**
     * Test error handling and reporting
     */
    public function test_error_handling_and_reporting(): void
    {
        $importJob = ImportJob::factory()->create();
        
        // Create validation error
        ImportError::createValidationError(
            $importJob->id,
            null,
            1,
            'INVALID_EMAIL',
            'Invalid email format',
            ['field' => 'email', 'value' => 'invalid-email'],
            ['email' => 'invalid-email']
        );

        // Create duplicate error
        ImportError::createDuplicateError(
            $importJob->id,
            null,
            2,
            'Employee already exists',
            ['employee_number' => 'EMP-12345678']
        );

        $errorStats = ImportError::getErrorStatistics($importJob->id);

        $this->assertEquals(2, $errorStats['total_errors']);
        $this->assertArrayHasKey('validation', $errorStats['by_type']);
        $this->assertArrayHasKey('duplicate', $errorStats['by_type']);
        $this->assertArrayHasKey('INVALID_EMAIL', $errorStats['by_code']);
        $this->assertArrayHasKey('DUPLICATE_EMPLOYEE', $errorStats['by_code']);
    }
}
