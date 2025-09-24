<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use App\Models\ImportJob;

class RedisJobService
{
    /**
     * Publish job update to Redis channel
     */
    public function publishJobUpdate(string $jobId, array $data = []): void
    {
        $channel = "job_updates:{$jobId}";
        
        // Get current job data if not provided
        if (empty($data)) {
            $job = ImportJob::where('job_id', $jobId)->first();
            if ($job) {
                $data = [
                    'job_id' => $job->job_id,
                    'status' => $job->status,
                    'total_rows' => $job->total_rows,
                    'processed_rows' => $job->processed_rows,
                    'successful_rows' => $job->successful_rows,
                    'failed_rows' => $job->failed_rows,
                    'progress_percentage' => $job->progress_percentage,
                    'created_at' => $job->created_at?->toISOString(),
                    'updated_at' => $job->updated_at?->toISOString(),
                    'completed_at' => $job->completed_at?->toISOString(),
                    'error_message' => $job->error_message,
                ];
            }
        }
        
        $message = [
            'type' => 'job_update',
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];
        
        Redis::publish($channel, json_encode($message));
    }
    
    /**
     * Publish job completion to Redis channel
     */
    public function publishJobCompletion(string $jobId, string $status, array $data = []): void
    {
        $channel = "job_updates:{$jobId}";
        
        $message = [
            'type' => 'job_completed',
            'status' => $status,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];
        
        Redis::publish($channel, json_encode($message));
    }
    
    /**
     * Publish job error to Redis channel
     */
    public function publishJobError(string $jobId, string $errorMessage): void
    {
        $channel = "job_updates:{$jobId}";
        
        $message = [
            'type' => 'job_error',
            'error' => $errorMessage,
            'timestamp' => now()->toISOString(),
        ];
        
        Redis::publish($channel, json_encode($message));
    }
    
    /**
     * Get job status from Redis cache
     */
    public function getJobStatus(string $jobId): ?array
    {
        $key = "job_status:{$jobId}";
        $cached = Redis::get($key);
        
        if ($cached) {
            return json_decode($cached, true);
        }
        
        // Fallback to database
        $job = ImportJob::where('job_id', $jobId)->first();
        if ($job) {
            $data = [
                'job_id' => $job->job_id,
                'status' => $job->status,
                'total_rows' => $job->total_rows,
                'processed_rows' => $job->processed_rows,
                'successful_rows' => $job->successful_rows,
                'failed_rows' => $job->failed_rows,
                'progress_percentage' => $job->progress_percentage,
                'created_at' => $job->created_at?->toISOString(),
                'updated_at' => $job->updated_at?->toISOString(),
                'completed_at' => $job->completed_at?->toISOString(),
                'error_message' => $job->error_message,
            ];
            
            // Cache for 5 minutes
            Redis::setex($key, 300, json_encode($data));
            
            return $data;
        }
        
        return null;
    }
    
    /**
     * Cache job status in Redis
     */
    public function cacheJobStatus(string $jobId, array $data): void
    {
        $key = "job_status:{$jobId}";
        Redis::setex($key, 300, json_encode($data)); // Cache for 5 minutes
    }
}
