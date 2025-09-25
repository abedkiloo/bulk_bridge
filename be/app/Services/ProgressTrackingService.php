<?php

namespace App\Services;

use App\Models\ImportJob;
use Illuminate\Support\Facades\Redis;

class ProgressTrackingService
{
    public function updateProgress(ImportJob $job): void
    {
        $progress = [
            'job_id' => $job->uuid,
            'status' => $job->status,
            'total_rows' => $job->total_rows,
            'processed_rows' => $job->processed_rows,
            'successful_rows' => $job->successful_rows,
            'failed_rows' => $job->failed_rows,
            'duplicate_rows' => $job->duplicate_rows,
            'progress_percentage' => $job->progress_percentage,
            'updated_at' => now()->toISOString()
        ];

        // Store in Redis for fast retrieval
        Redis::hmset(
            "import:progress:{$job->uuid}",
            $progress
        );

        // Publish to Redis pub/sub for real-time updates
        Redis::publish(
            'import-progress-updates',
            json_encode([
                'event' => 'progress_updated',
                'job_id' => $job->uuid,
                'data' => $progress
            ])
        );
    }

    public function getProgress(string $jobId): ?array
    {
        $progress = Redis::hgetall("import:progress:{$jobId}");
        return empty($progress) ? null : $progress;
    }

    public function getProgressStream(string $jobId): array
    {
        // For pub/sub, we'll use polling with Redis keys
        // In a real implementation, you'd use WebSockets or SSE
        $progress = $this->getProgress($jobId);
        return $progress ? [$progress] : [];
    }

    public function clearProgress(string $jobId): void
    {
        Redis::del("import:progress:{$jobId}");
    }
}
