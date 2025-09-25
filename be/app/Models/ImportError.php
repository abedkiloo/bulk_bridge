<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_job_id',
        'row_number',
        'raw_data',
        'error_type',
        'error_code',
        'error_message',
        'error_details',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'error_details' => 'array',
    ];

    // Relationships
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class, 'import_job_id', 'uuid');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('error_type', $type);
    }

    public function scopeValidationErrors($query)
    {
        return $query->where('error_type', 'validation');
    }

    public function scopeDuplicateErrors($query)
    {
        return $query->where('error_type', 'duplicate');
    }

    public function scopeSystemErrors($query)
    {
        return $query->where('error_type', 'system');
    }

    // Static factory methods
    public static function createValidationError(string $importJobId, int $rowNumber, array $rawData, array $errors): self
    {
        return self::create([
            'import_job_id' => $importJobId,
            'row_number' => $rowNumber,
            'raw_data' => $rawData,
            'error_type' => 'validation',
            'error_code' => 'VALIDATION_FAILED',
            'error_message' => implode('; ', \Illuminate\Support\Arr::flatten($errors)),
            'error_details' => $errors,
        ]);
    }

    public static function createDuplicateError(string $importJobId, int $rowNumber, array $rawData, string $reason = 'Employee already exists'): self
    {
        return self::create([
            'import_job_id' => $importJobId,
            'row_number' => $rowNumber,
            'raw_data' => $rawData,
            'error_type' => 'duplicate',
            'error_code' => 'DUPLICATE_EMPLOYEE',
            'error_message' => $reason,
            'error_details' => ['reason' => $reason],
        ]);
    }

    public static function createSystemError(string $importJobId, int $rowNumber, array $rawData, string $error): self
    {
        return self::create([
            'import_job_id' => $importJobId,
            'row_number' => $rowNumber,
            'raw_data' => $rawData,
            'error_type' => 'system',
            'error_code' => 'SYSTEM_ERROR',
            'error_message' => $error,
            'error_details' => ['error' => $error],
        ]);
    }
}