<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class ImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'filename',
        'original_filename',
        'file_path',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'duplicate_rows',
        'status',
        'metadata',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $dates = [
        'started_at',
        'completed_at',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->job_id)) {
                $model->job_id = Str::uuid();
            }
        });
    }

    /**
     * Get import rows for this job
     */
    public function importRows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    /**
     * Get import errors for this job
     */
    public function importErrors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    /**
     * Get employees imported in this job
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'last_import_job_id', 'job_id');
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }
        
        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->processed_rows === 0) {
            return 0;
        }
        
        return round(($this->successful_rows / $this->processed_rows) * 100, 2);
    }

    /**
     * Check if job is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }

    /**
     * Check if job is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Mark job as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
        
        $this->clearProgressCache();
    }

    /**
     * Mark job as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        $this->clearProgressCache();
    }

    /**
     * Mark job as failed
     */
    public function markAsFailed(?string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
        
        $this->clearProgressCache();
    }

    /**
     * Update progress counters
     */
    public function updateProgress(): void
    {
        $this->processed_rows = $this->importRows()->count();
        $this->successful_rows = $this->importRows()->where('status', 'success')->count();
        $this->failed_rows = $this->importRows()->whereIn('status', ['failed', 'skipped'])->count();
        $this->duplicate_rows = $this->importRows()->where('status', 'duplicate')->count();
        
        $this->save();
        $this->clearProgressCache();
    }

    /**
     * Get cached progress data for real-time updates
     */
    public function getCachedProgress(): array
    {
        $cacheKey = "import_job_progress_{$this->job_id}";
        
        return Cache::remember($cacheKey, 30, function () {
            return [
                'job_id' => $this->job_id,
                'status' => $this->status,
                'total_rows' => $this->total_rows,
                'processed_rows' => $this->processed_rows,
                'successful_rows' => $this->successful_rows,
                'failed_rows' => $this->failed_rows,
                'duplicate_rows' => $this->duplicate_rows,
                'progress_percentage' => $this->progress_percentage,
                'success_rate' => $this->success_rate,
                'started_at' => $this->started_at?->toISOString(),
                'completed_at' => $this->completed_at?->toISOString(),
                'updated_at' => $this->updated_at->toISOString(),
            ];
        });
    }

    /**
     * Clear progress cache
     */
    public function clearProgressCache(): void
    {
        $cacheKey = "import_job_progress_{$this->job_id}";
        Cache::forget($cacheKey);
    }

    /**
     * Scope for active jobs
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    /**
     * Scope for completed jobs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed jobs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get job statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'successful_rows' => $this->successful_rows,
            'failed_rows' => $this->failed_rows,
            'duplicate_rows' => $this->duplicate_rows,
            'progress_percentage' => $this->progress_percentage,
            'success_rate' => $this->success_rate,
            'error_count' => $this->importErrors()->count(),
            'processing_time' => $this->getProcessingTime(),
        ];
    }

    /**
     * Get processing time in seconds
     */
    public function getProcessingTime(): ?int
    {
        if (!$this->started_at) {
            return null;
        }
        
        $endTime = $this->completed_at ?? now();
        return $endTime->diffInSeconds($this->started_at);
    }
}
