<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImportError extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_job_id',
        'import_row_id',
        'row_number',
        'error_type',
        'error_code',
        'error_message',
        'error_context',
        'raw_data',
    ];

    protected $casts = [
        'error_context' => 'array',
        'raw_data' => 'array',
    ];

    /**
     * Get the import job that owns this error
     */
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    /**
     * Get the import row that owns this error
     */
    public function importRow(): BelongsTo
    {
        return $this->belongsTo(ImportRow::class);
    }

    /**
     * Create a validation error
     */
    public static function createValidationError(
        int $importJobId,
        ?int $importRowId,
        ?int $rowNumber,
        string $errorCode,
        string $errorMessage,
        array $errorContext = [],
        array $rawData = []
    ): self {
        return self::create([
            'import_job_id' => $importJobId,
            'import_row_id' => $importRowId,
            'row_number' => $rowNumber,
            'error_type' => 'validation',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'error_context' => $errorContext,
            'raw_data' => $rawData,
        ]);
    }

    /**
     * Create a duplicate error
     */
    public static function createDuplicateError(
        int $importJobId,
        ?int $importRowId,
        ?int $rowNumber,
        string $errorMessage,
        array $rawData = []
    ): self {
        return self::create([
            'import_job_id' => $importJobId,
            'import_row_id' => $importRowId,
            'row_number' => $rowNumber,
            'error_type' => 'duplicate',
            'error_code' => 'DUPLICATE_EMPLOYEE',
            'error_message' => $errorMessage,
            'raw_data' => $rawData,
        ]);
    }

    /**
     * Create a system error
     */
    public static function createSystemError(
        int $importJobId,
        ?int $importRowId,
        ?int $rowNumber,
        string $errorCode,
        string $errorMessage,
        array $errorContext = [],
        array $rawData = []
    ): self {
        return self::create([
            'import_job_id' => $importJobId,
            'import_row_id' => $importRowId,
            'row_number' => $rowNumber,
            'error_type' => 'system',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'error_context' => $errorContext,
            'raw_data' => $rawData,
        ]);
    }

    /**
     * Create a business logic error
     */
    public static function createBusinessLogicError(
        int $importJobId,
        ?int $importRowId,
        ?int $rowNumber,
        string $errorCode,
        string $errorMessage,
        array $errorContext = [],
        array $rawData = []
    ): self {
        return self::create([
            'import_job_id' => $importJobId,
            'import_row_id' => $importRowId,
            'row_number' => $rowNumber,
            'error_type' => 'business_logic',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'error_context' => $errorContext,
            'raw_data' => $rawData,
        ]);
    }

    /**
     * Get formatted error message with context
     */
    public function getFormattedMessage(): string
    {
        $message = $this->error_message;
        
        if (!empty($this->error_context)) {
            $context = json_encode($this->error_context, JSON_PRETTY_PRINT);
            $message .= "\nContext: {$context}";
        }
        
        if ($this->row_number) {
            $message = "Row {$this->row_number}: {$message}";
        }
        
        return $message;
    }

    /**
     * Scope for validation errors
     */
    public function scopeValidation($query)
    {
        return $query->where('error_type', 'validation');
    }

    /**
     * Scope for duplicate errors
     */
    public function scopeDuplicate($query)
    {
        return $query->where('error_type', 'duplicate');
    }

    /**
     * Scope for system errors
     */
    public function scopeSystem($query)
    {
        return $query->where('error_type', 'system');
    }

    /**
     * Scope for business logic errors
     */
    public function scopeBusinessLogic($query)
    {
        return $query->where('error_type', 'business_logic');
    }

    /**
     * Scope for errors by code
     */
    public function scopeByCode($query, string $errorCode)
    {
        return $query->where('error_code', $errorCode);
    }

    /**
     * Get error statistics for a job
     */
    public static function getErrorStatistics(int $importJobId): array
    {
        $errors = self::where('import_job_id', $importJobId)
            ->selectRaw('error_type, error_code, COUNT(*) as count')
            ->groupBy('error_type', 'error_code')
            ->get();

        $statistics = [
            'total_errors' => $errors->sum('count'),
            'by_type' => $errors->groupBy('error_type')->map->sum('count'),
            'by_code' => $errors->groupBy('error_code')->map->sum('count'),
            'detailed' => $errors->toArray(),
        ];

        return $statistics;
    }
}
