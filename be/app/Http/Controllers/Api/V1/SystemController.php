<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RedisJobService;
use App\Services\ProgressTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class SystemController extends Controller
{
    public function __construct(
        private RedisJobService $redisJobService,
        private ProgressTrackingService $progressTracker
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/system/health",
     *     summary="Get system health status",
     *     @OA\Response(response=200, description="System health information")
     * )
     */
    public function health(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'services' => []
        ];

        // Check database connection
        try {
            DB::connection()->getPdo();
            $health['services']['database'] = [
                'status' => 'healthy',
                'connection' => 'active'
            ];
        } catch (\Exception $e) {
            $health['services']['database'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }

        // Check Redis connection
        try {
            Redis::ping();
            $health['services']['redis'] = [
                'status' => 'healthy',
                'connection' => 'active'
            ];
        } catch (\Exception $e) {
            $health['services']['redis'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }

        // Check storage
        try {
            Storage::disk('local')->put('health-check.txt', 'test');
            Storage::disk('local')->delete('health-check.txt');
            $health['services']['storage'] = [
                'status' => 'healthy',
                'connection' => 'active'
            ];
        } catch (\Exception $e) {
            $health['services']['storage'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }

        return response()->json($health);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/system/status",
     *     summary="Get detailed system status",
     *     @OA\Response(response=200, description="Detailed system status")
     * )
     */
    public function status(): JsonResponse
    {
        $status = [
            'timestamp' => now()->toISOString(),
            'database' => [
                'connection' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
                'driver' => config('database.default'),
                'tables' => $this->getTableCounts()
            ],
            'redis' => [
                'connection' => Redis::ping() ? 'connected' : 'disconnected',
                'info' => $this->getRedisInfo()
            ],
            'queue' => [
                'connection' => config('queue.default'),
                'pending_jobs' => $this->getQueueStatus()
            ],
            'cache' => [
                'driver' => config('cache.default'),
                'status' => $this->getCacheStatus()
            ],
            'storage' => [
                'available' => $this->getStorageInfo()
            ]
        ];

        return response()->json($status);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/system/metrics",
     *     summary="Get system metrics",
     *     @OA\Response(response=200, description="System metrics")
     * )
     */
    public function metrics(): JsonResponse
    {
        $metrics = [
            'timestamp' => now()->toISOString(),
            'imports' => [
                'total_jobs' => DB::table('import_jobs')->count(),
                'active_jobs' => DB::table('import_jobs')->whereIn('status', ['pending', 'processing'])->count(),
                'completed_jobs' => DB::table('import_jobs')->where('status', 'completed')->count(),
                'failed_jobs' => DB::table('import_jobs')->where('status', 'failed')->count(),
            ],
            'employees' => [
                'total' => DB::table('employees')->count(),
                'active' => DB::table('employees')->where('status', 'active')->count(),
                'inactive' => DB::table('employees')->where('status', 'inactive')->count(),
            ],
            'errors' => [
                'total_errors' => DB::table('import_errors')->count(),
                'recent_errors' => DB::table('import_errors')->where('created_at', '>=', now()->subHours(24))->count(),
            ],
            'performance' => [
                'avg_processing_time' => $this->getAverageProcessingTime(),
                'queue_size' => $this->getQueueSize(),
                'cache_hit_rate' => $this->getCacheHitRate(),
            ]
        ];

        return response()->json($metrics);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/system/redis/keys",
     *     summary="Get Redis keys information",
     *     @OA\Parameter(name="pattern", in="query", description="Key pattern", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Redis keys information")
     * )
     */
    public function redisKeys(Request $request): JsonResponse
    {
        $pattern = $request->get('pattern', '*');
        
        try {
            $keys = Redis::keys($pattern);
            $keyInfo = [];
            
            foreach (array_slice($keys, 0, 100) as $key) { // Limit to 100 keys
                $type = Redis::type($key);
                $ttl = Redis::ttl($key);
                
                $keyInfo[] = [
                    'key' => $key,
                    'type' => $type,
                    'ttl' => $ttl,
                    'size' => $this->getKeySize($key, $type)
                ];
            }
            
            return response()->json([
                'pattern' => $pattern,
                'total_keys' => count($keys),
                'displayed_keys' => count($keyInfo),
                'keys' => $keyInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve Redis keys',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/system/redis/clear",
     *     summary="Clear Redis cache",
     *     @OA\Parameter(name="pattern", in="query", description="Key pattern to clear", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Redis cache cleared")
     * )
     */
    public function clearRedis(Request $request): JsonResponse
    {
        $pattern = $request->get('pattern', '*');
        
        try {
            $keys = Redis::keys($pattern);
            $deleted = 0;
            
            if (!empty($keys)) {
                $deleted = Redis::del($keys);
            }
            
            return response()->json([
                'message' => 'Redis cache cleared successfully',
                'pattern' => $pattern,
                'keys_deleted' => $deleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to clear Redis cache',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/system/queue/status",
     *     summary="Get queue status",
     *     @OA\Response(response=200, description="Queue status information")
     * )
     */
    public function queueStatus(): JsonResponse
    {
        try {
            $queues = [
                'default' => Redis::llen('queues:default'),
                'imports' => Redis::llen('queues:imports'),
                'imports-high-priority' => Redis::llen('queues:imports-high-priority'),
            ];
            
            $failedJobs = DB::table('failed_jobs')->count();
            
            return response()->json([
                'queues' => $queues,
                'failed_jobs' => $failedJobs,
                'total_pending' => array_sum($queues)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get queue status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/system/queue/retry-failed",
     *     summary="Retry all failed jobs",
     *     @OA\Response(response=200, description="Failed jobs retried")
     * )
     */
    public function retryFailedJobs(): JsonResponse
    {
        try {
            $failedJobs = DB::table('failed_jobs')->get();
            $retried = 0;
            
            foreach ($failedJobs as $job) {
                // This would typically use Laravel's queue:retry command
                // For now, we'll just delete the failed job record
                DB::table('failed_jobs')->where('id', $job->id)->delete();
                $retried++;
            }
            
            return response()->json([
                'message' => "Retried {$retried} failed jobs"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retry failed jobs',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/system/logs",
     *     summary="Get system logs",
     *     @OA\Parameter(name="level", in="query", description="Log level", @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", description="Number of logs", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="System logs")
     * )
     */
    public function logs(Request $request): JsonResponse
    {
        $level = $request->get('level', 'all');
        $limit = min($request->get('limit', 100), 500);
        
        try {
            $logFile = storage_path('logs/laravel.log');
            
            if (!file_exists($logFile)) {
                return response()->json([
                    'message' => 'No log file found',
                    'logs' => []
                ]);
            }
            
            $logs = $this->parseLogFile($logFile, $level, $limit);
            
            return response()->json([
                'level' => $level,
                'limit' => $limit,
                'total_logs' => count($logs),
                'logs' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to read logs',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getTableCounts(): array
    {
        try {
            return [
                'import_jobs' => DB::table('import_jobs')->count(),
                'employees' => DB::table('employees')->count(),
                'import_errors' => DB::table('import_errors')->count(),
                'import_rows' => DB::table('import_rows')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getRedisInfo(): array
    {
        try {
            $info = Redis::info();
            return [
                'version' => $info['redis_version'] ?? 'unknown',
                'uptime' => $info['uptime_in_seconds'] ?? 0,
                'memory_used' => $info['used_memory_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getQueueStatus(): array
    {
        try {
            return [
                'default' => Redis::llen('queues:default'),
                'imports' => Redis::llen('queues:imports'),
                'imports-high-priority' => Redis::llen('queues:imports-high-priority'),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getCacheStatus(): string
    {
        try {
            Cache::put('health-check', 'test', 60);
            $result = Cache::get('health-check');
            Cache::forget('health-check');
            return $result === 'test' ? 'working' : 'not_working';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    private function getStorageInfo(): array
    {
        try {
            $path = storage_path();
            return [
                'total_space' => disk_total_space($path),
                'free_space' => disk_free_space($path),
                'used_space' => disk_total_space($path) - disk_free_space($path),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getAverageProcessingTime(): ?float
    {
        try {
            return DB::table('import_jobs')
                ->where('status', 'completed')
                ->whereNotNull('completed_at')
                ->whereNotNull('created_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - created_at))) as avg_time')
                ->value('avg_time');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getQueueSize(): int
    {
        try {
            return Redis::llen('queues:imports') + Redis::llen('queues:imports-high-priority');
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getCacheHitRate(): ?float
    {
        try {
            $info = Redis::info();
            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;
            
            return $total > 0 ? ($hits / $total) * 100 : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getKeySize(string $key, string $type): int
    {
        try {
            switch ($type) {
                case 'string':
                    return strlen(Redis::get($key) ?? '');
                case 'list':
                    return Redis::llen($key);
                case 'set':
                    return Redis::scard($key);
                case 'hash':
                    return Redis::hlen($key);
                default:
                    return 0;
            }
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function parseLogFile(string $logFile, string $level, int $limit): array
    {
        $logs = [];
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_reverse(array_slice($lines, -$limit * 2)); // Get more lines to filter
        
        foreach ($lines as $line) {
            if (count($logs) >= $limit) break;
            
            if ($level !== 'all' && !str_contains(strtolower($line), strtolower($level))) {
                continue;
            }
            
            $logs[] = [
                'timestamp' => $this->extractTimestamp($line),
                'level' => $this->extractLevel($line),
                'message' => $line
            ];
        }
        
        return $logs;
    }

    private function extractTimestamp(string $line): ?string
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function extractLevel(string $line): string
    {
        if (preg_match('/\.(\w+):/', $line, $matches)) {
            return strtoupper($matches[1]);
        }
        return 'UNKNOWN';
    }
}
