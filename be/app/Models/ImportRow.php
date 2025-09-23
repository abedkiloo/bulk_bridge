<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImportRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_job_id',
        'row_number',
        'raw_data',
        'status',
        'employee_id',
        'error_message',
        'validation_errors',
        'processed_at',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'validation_errors' => 'array',
        'processed_at' => 'datetime',
    ];

    protected $dates = [
        'processed_at',
    ];

    /**
     * Get the import job that owns this row
     */
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    /**
     * Get the employee associated with this row
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /**
     * Get import errors for this row
     */
    public function importErrors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    /**
     * Mark row as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark row as successful
     */
    public function markAsSuccess(string $employeeId): void
    {
        $this->update([
            'status' => 'success',
            'employee_id' => $employeeId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark row as failed
     */
    public function markAsFailed(string $errorMessage, array $validationErrors = []): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'validation_errors' => $validationErrors,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark row as duplicate
     */
    public function markAsDuplicate(string $errorMessage): void
    {
        $this->update([
            'status' => 'duplicate',
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark row as skipped
     */
    public function markAsSkipped(string $errorMessage): void
    {
        $this->update([
            'status' => 'skipped',
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    /**
     * Check if row is processed
     */
    public function isProcessed(): bool
    {
        return in_array($this->status, ['success', 'failed', 'duplicate', 'skipped']);
    }

    /**
     * Check if row is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if row has errors
     */
    public function hasErrors(): bool
    {
        return in_array($this->status, ['failed', 'duplicate', 'skipped']);
    }

    /**
     * Get formatted error message
     */
    public function getFormattedErrorMessage(): string
    {
        if (empty($this->error_message)) {
            return 'No error message available';
        }

        $message = $this->error_message;
        
        if (!empty($this->validation_errors)) {
            $message .= "\nValidation errors: " . implode(', ', $this->validation_errors);
        }

        return $message;
    }

    /**
     * Scope for pending rows
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processing rows
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for successful rows
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed rows
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'duplicate', 'skipped']);
    }

    /**
     * Scope for rows with errors
     */
    public function scopeWithErrors($query)
    {
        return $query->whereIn('status', ['failed', 'duplicate', 'skipped']);
    }

    /**
     * Get row data for processing
     */
    public function getRowData(): array
    {
        return $this->raw_data ?? [];
    }

    /**
     * Set row data
     */
    public function setRowData(array $data): void
    {
        $this->update(['raw_data' => $data]);
    }
}
