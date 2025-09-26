<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class ImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'original_filename',
        'file_path',
        'file_size',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'duplicate_rows',
        'status',
        'error_message',
        'progress_percentage',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percentage' => 'decimal:2',
        'file_size' => 'integer',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
        'duplicate_rows' => 'integer',
    ];

    protected $dates = [
        'started_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'import_job_id', 'uuid');
    }

    public function importErrors(): HasMany
    {
        return $this->hasMany(ImportError::class, 'import_job_id', 'uuid');
    }


    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    // Accessors
    public function getProgressPercentageAttribute($value)
    {
        if ($this->total_rows > 0) {
            return round(($this->processed_rows / $this->total_rows) * 100, 2);
        }
        return 0;
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDurationAttribute()
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at);
        }
        return null;
    }

    // Status checks
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }

    // Business logic methods
    public function startProcessing(): void
    {
        if (!$this->isPending()) {
            throw new \DomainException('Import job can only be started from pending status');
        }

        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function updateProgress(int $processed, int $successful, int $failed, int $duplicates): void
    {
        if (!$this->isProcessing()) {
            throw new \DomainException('Progress can only be updated for processing jobs');
        }

        $progressPercentage = $this->total_rows > 0 ? round(($processed / $this->total_rows) * 100, 2) : 0;

        $this->update([
            'processed_rows' => $processed,
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'duplicate_rows' => $duplicates,
            'progress_percentage' => $progressPercentage,
        ]);
    }

    public function complete(): void
    {
        if (!$this->isProcessing()) {
            throw new \DomainException('Only processing jobs can be completed');
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress_percentage' => 100.00,
        ]);
    }

    public function fail(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        if ($this->isFinished()) {
            throw new \DomainException('Cannot cancel finished jobs');
        }

        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    // Static factory methods
    public static function createFromUpload(string $uuid, string $filename, string $filePath, int $fileSize, int $totalRows): self
    {
        return self::create([
            'uuid' => $uuid,
            'original_filename' => $filename,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'total_rows' => $totalRows,
            'status' => 'pending',
        ]);
    }
}