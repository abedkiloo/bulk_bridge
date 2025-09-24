# 🔄 Complete Data Flow Guide

## Step-by-Step Process Flow

### 1. **File Upload Phase**
```
Client Upload → ImportController::store() → File Validation → Storage
```

**Detailed Steps:**
1. **Client sends POST request** to `/api/imports` with CSV file
2. **StoreImportRequest validates** file (size, type, etc.)
3. **ImportController::store()** receives validated request
4. **File is stored** with unique UUID filename
5. **CsvParserService validates** file structure and headers
6. **ImportJob record created** in database
7. **ProcessBulkImportJob dispatched** to queue

### 2. **CSV Processing Phase**
```
ProcessBulkImportJob → CsvParserService → ImportRow Creation → Batch Dispatch
```

**Detailed Steps:**
1. **Queue worker picks up** ProcessBulkImportJob
2. **CSV file parsed** using League CSV library
3. **Headers validated** against expected format
4. **ImportRow records created** for each data row
5. **Batch jobs dispatched** for parallel processing

### 3. **Row Processing Phase**
```
ProcessImportRowJob → EmployeeValidationService → Employee Upsert → Status Update
```

**Detailed Steps:**
1. **ProcessImportRowJob processes** batch of rows
2. **EmployeeValidationService validates** each row
3. **Duplicate checking** against existing employees
4. **Employee upsert** (create or update)
5. **ImportRow status updated** (success/failed/duplicate)
6. **Error logging** for failed rows

### 4. **Progress Tracking Phase**
```
Database Updates → Cache Updates → API Responses → Real-time Monitoring
```

**Detailed Steps:**
1. **ImportJob progress updated** after each batch
2. **Cache updated** for fast API responses
3. **Statistics calculated** (success rate, error counts)
4. **API endpoints return** current status
5. **Client can monitor** progress in real-time

## Key Loops and Iterations

### 1. **CSV Parsing Loop**
```php
foreach ($records as $record) {
    $data[] = array_values($record); // Convert to array
}
```

### 2. **Batch Processing Loop**
```php
foreach ($importRowIds as $batch) {
    ProcessImportRowJob::dispatch($importJobId, $batch);
}
```

### 3. **Row Validation Loop**
```php
foreach ($rows as $row) {
    $validation = $this->employeeValidation->validateRow($row);
    if (!$validation['valid']) {
        // Handle validation errors
    }
}
```

### 4. **Error Processing Loop**
```php
foreach ($errors as $error) {
    ImportError::create([
        'import_job_id' => $importJobId,
        'error_type' => $error['type'],
        'error_message' => $error['message'],
    ]);
}
```

## Error Handling Flow

### 1. **Validation Errors**
```
Invalid Data → ValidationService → ImportError → Skip Row → Continue Processing
```

### 2. **System Errors**
```
Exception → Log Error → Mark Job Failed → Return Error Response
```

### 3. **Retry Logic**
```
Job Failure → Queue Retry → Max Attempts → Mark Failed → Error Logging
```

## Performance Optimizations

### 1. **Database Indexes**
- Unique indexes on employee_number and email
- Composite indexes for common queries
- Foreign key indexes for relationships

### 2. **Batch Processing**
- Process rows in batches of 100
- Parallel job execution
- Memory-efficient streaming

### 3. **Caching Strategy**
- Progress data cached for fast API responses
- Cache invalidation on updates
- Redis for session and queue data

### 4. **Queue Management**
- Separate queue for import jobs
- Timeout and retry configuration
- Dead letter queue for failed jobs
