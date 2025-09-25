<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Import Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for CSV import processing including batch sizes,
    | file limits, and processing timeouts.
    |
    */

    'batch_size' => env('IMPORT_BATCH_SIZE', 1000),
    'max_file_size' => env('IMPORT_MAX_FILE_SIZE', 20 * 1024 * 1024), // 20MB
    'max_rows' => env('IMPORT_MAX_ROWS', 50000),
    'timeout' => env('IMPORT_TIMEOUT', 3600),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue settings for import processing jobs.
    |
    */

    'queue' => [
        'connection' => 'redis-imports',
        'queue' => 'imports-high-priority',
        'timeout' => 3600,
        'tries' => 3,
        'backoff' => [30, 60, 120],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Validation rules for employee data import.
    |
    */

    'validation' => [
        'employee_number' => ['required', 'string', 'max:50'],
        'first_name' => ['required', 'string', 'max:100'],
        'last_name' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:255'],
        'department' => ['required', 'string', 'max:100'],
        'salary' => ['required', 'numeric', 'min:0'],
        'currency' => ['required', 'string', 'size:3'],
        'country_code' => ['required', 'string', 'size:2'],
        'start_date' => ['required', 'date', 'before_or_equal:today'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Expected CSV Headers
    |--------------------------------------------------------------------------
    |
    | The expected headers for CSV import files.
    |
    */

    'expected_headers' => [
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'department',
        'salary',
        'currency',
        'country_code',
        'start_date',
    ],
];
