<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ImportJob;
use App\Models\ImportError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/statistics/overview",
     *     summary="Get system overview statistics",
     *     @OA\Response(response=200, description="Overview statistics")
     * )
     */
    public function overview(): JsonResponse
    {
        $stats = [
            'timestamp' => now()->toISOString(),
            'imports' => [
                'total_jobs' => ImportJob::count(),
                'successful_jobs' => ImportJob::where('status', 'completed')->count(),
                'failed_jobs' => ImportJob::where('status', 'failed')->count(),
                'active_jobs' => ImportJob::whereIn('status', ['pending', 'processing'])->count(),
                'total_rows_processed' => ImportJob::sum('total_rows'),
                'total_successful_rows' => ImportJob::sum('successful_rows'),
                'total_failed_rows' => ImportJob::sum('failed_rows'),
                'total_duplicate_rows' => ImportJob::sum('duplicate_rows'),
            ],
            'employees' => [
                'total_employees' => Employee::count(),
                'active_employees' => Employee::where('status', 'active')->count(),
                'inactive_employees' => Employee::where('status', 'inactive')->count(),
                'terminated_employees' => Employee::where('status', 'terminated')->count(),
                'unique_departments' => Employee::distinct('department')->count('department'),
                'average_salary' => Employee::whereNotNull('salary')->avg('salary'),
                'total_salary_cost' => Employee::whereNotNull('salary')->sum('salary'),
            ],
            'errors' => [
                'total_errors' => ImportError::count(),
                'recent_errors' => ImportError::where('created_at', '>=', now()->subDays(7))->count(),
                'error_types' => $this->getErrorTypeBreakdown(),
            ],
            'performance' => [
                'average_processing_time' => $this->getAverageProcessingTime(),
                'fastest_import' => $this->getFastestImport(),
                'largest_import' => $this->getLargestImport(),
                'success_rate' => $this->getSuccessRate(),
            ]
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/statistics/imports",
     *     summary="Get import statistics",
     *     @OA\Parameter(name="period", in="query", description="Time period (day, week, month, year)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="start_date", in="query", description="Start date (YYYY-MM-DD)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="end_date", in="query", description="End date (YYYY-MM-DD)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Import statistics")
     * )
     */
    public function imports(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Set date range based on period or custom dates
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } else {
            [$start, $end] = $this->getDateRange($period);
        }

        $stats = [
            'period' => $period,
            'date_range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString()
            ],
            'summary' => $this->getImportSummary($start, $end),
            'daily_breakdown' => $this->getDailyImportBreakdown($start, $end),
            'status_distribution' => $this->getImportStatusDistribution($start, $end),
            'processing_times' => $this->getProcessingTimeStats($start, $end),
            'file_sizes' => $this->getFileSizeStats($start, $end),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/statistics/employees",
     *     summary="Get employee statistics",
     *     @OA\Parameter(name="department", in="query", description="Filter by department", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Employee statistics")
     * )
     */
    public function employees(Request $request): JsonResponse
    {
        $department = $request->get('department');
        $query = Employee::query();

        if ($department) {
            $query->where('department', $department);
        }

        $stats = [
            'filters' => [
                'department' => $department
            ],
            'overview' => [
                'total' => $query->count(),
                'active' => $query->clone()->where('status', 'active')->count(),
                'inactive' => $query->clone()->where('status', 'inactive')->count(),
                'terminated' => $query->clone()->where('status', 'terminated')->count(),
            ],
            'departments' => $this->getDepartmentStats(),
            'salary_analysis' => $this->getSalaryAnalysis($query->clone()),
            'hire_date_analysis' => $this->getHireDateAnalysis($query->clone()),
            'geographic_distribution' => $this->getGeographicDistribution($query->clone()),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/statistics/errors",
     *     summary="Get error statistics",
     *     @OA\Parameter(name="period", in="query", description="Time period", @OA\Schema(type="string")),
     *     @OA\Parameter(name="error_type", in="query", description="Filter by error type", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Error statistics")
     * )
     */
    public function errors(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $errorType = $request->get('error_type');
        
        [$start, $end] = $this->getDateRange($period);
        
        $query = ImportError::whereBetween('created_at', [$start, $end]);
        
        if ($errorType) {
            $query->where('error_type', $errorType);
        }

        $stats = [
            'period' => $period,
            'filters' => [
                'error_type' => $errorType
            ],
            'summary' => [
                'total_errors' => $query->count(),
                'unique_jobs_with_errors' => $query->distinct('import_job_id')->count('import_job_id'),
                'average_errors_per_job' => $this->getAverageErrorsPerJob($start, $end),
            ],
            'error_types' => $this->getErrorTypeBreakdown($start, $end),
            'top_errors' => $this->getTopErrors($start, $end),
            'error_trends' => $this->getErrorTrends($start, $end),
            'jobs_with_most_errors' => $this->getJobsWithMostErrors($start, $end),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/statistics/performance",
     *     summary="Get performance statistics",
     *     @OA\Parameter(name="period", in="query", description="Time period", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Performance statistics")
     * )
     */
    public function performance(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        [$start, $end] = $this->getDateRange($period);

        $stats = [
            'period' => $period,
            'processing_metrics' => [
                'average_processing_time' => $this->getAverageProcessingTime($start, $end),
                'fastest_processing_time' => $this->getFastestProcessingTime($start, $end),
                'slowest_processing_time' => $this->getSlowestProcessingTime($start, $end),
                'total_processing_time' => $this->getTotalProcessingTime($start, $end),
            ],
            'throughput_metrics' => [
                'average_rows_per_minute' => $this->getAverageRowsPerMinute($start, $end),
                'peak_throughput' => $this->getPeakThroughput($start, $end),
                'total_rows_processed' => $this->getTotalRowsProcessed($start, $end),
            ],
            'success_metrics' => [
                'overall_success_rate' => $this->getSuccessRate($start, $end),
                'average_success_rate' => $this->getAverageSuccessRate($start, $end),
                'jobs_with_100_percent_success' => $this->getJobsWith100PercentSuccess($start, $end),
            ],
            'resource_usage' => [
                'average_file_size' => $this->getAverageFileSize($start, $end),
                'largest_file_processed' => $this->getLargestFileProcessed($start, $end),
                'total_data_processed' => $this->getTotalDataProcessed($start, $end),
            ]
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/statistics/trends",
     *     summary="Get trend analysis",
     *     @OA\Parameter(name="metric", in="query", description="Metric to analyze (imports, employees, errors)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="period", in="query", description="Time period", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Trend analysis")
     * )
     */
    public function trends(Request $request): JsonResponse
    {
        $metric = $request->get('metric', 'imports');
        $period = $request->get('period', 'month');
        [$start, $end] = $this->getDateRange($period);

        $trends = [
            'metric' => $metric,
            'period' => $period,
            'date_range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString()
            ],
            'data' => $this->getTrendData($metric, $start, $end),
            'analysis' => $this->getTrendAnalysis($metric, $start, $end)
        ];

        return response()->json(['data' => $trends]);
    }

    private function getDateRange(string $period): array
    {
        $end = now();
        
        switch ($period) {
            case 'day':
                $start = $end->copy()->subDay();
                break;
            case 'week':
                $start = $end->copy()->subWeek();
                break;
            case 'month':
                $start = $end->copy()->subMonth();
                break;
            case 'year':
                $start = $end->copy()->subYear();
                break;
            default:
                $start = $end->copy()->subMonth();
        }
        
        return [$start, $end];
    }

    private function getImportSummary(Carbon $start, Carbon $end): array
    {
        $jobs = ImportJob::whereBetween('created_at', [$start, $end]);
        
        return [
            'total_jobs' => $jobs->count(),
            'completed_jobs' => $jobs->clone()->where('status', 'completed')->count(),
            'failed_jobs' => $jobs->clone()->where('status', 'failed')->count(),
            'active_jobs' => $jobs->clone()->whereIn('status', ['pending', 'processing'])->count(),
            'total_rows' => $jobs->sum('total_rows'),
            'successful_rows' => $jobs->sum('successful_rows'),
            'failed_rows' => $jobs->sum('failed_rows'),
            'duplicate_rows' => $jobs->sum('duplicate_rows'),
        ];
    }

    private function getDailyImportBreakdown(Carbon $start, Carbon $end): array
    {
        return ImportJob::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as jobs, SUM(total_rows) as total_rows, SUM(successful_rows) as successful_rows')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getImportStatusDistribution(Carbon $start, Carbon $end): array
    {
        return ImportJob::whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function getProcessingTimeStats(Carbon $start, Carbon $end): array
    {
        $times = ImportJob::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->selectRaw('EXTRACT(EPOCH FROM (completed_at - created_at)) as processing_time')
            ->pluck('processing_time')
            ->filter();

        if ($times->isEmpty()) {
            return ['average' => 0, 'min' => 0, 'max' => 0];
        }

        return [
            'average' => round($times->avg(), 2),
            'min' => round($times->min(), 2),
            'max' => round($times->max(), 2),
            'median' => round($times->sort()->values()->get(floor($times->count() / 2)), 2)
        ];
    }

    private function getFileSizeStats(Carbon $start, Carbon $end): array
    {
        $sizes = ImportJob::whereBetween('created_at', [$start, $end])
            ->whereNotNull('file_size')
            ->pluck('file_size')
            ->filter();

        if ($sizes->isEmpty()) {
            return ['average' => 0, 'min' => 0, 'max' => 0, 'total' => 0];
        }

        return [
            'average' => round($sizes->avg(), 2),
            'min' => $sizes->min(),
            'max' => $sizes->max(),
            'total' => $sizes->sum()
        ];
    }

    private function getDepartmentStats(): array
    {
        return Employee::select('department', DB::raw('COUNT(*) as count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    private function getSalaryAnalysis($query): array
    {
        $salaries = $query->whereNotNull('salary')->pluck('salary');
        
        if ($salaries->isEmpty()) {
            return ['average' => 0, 'min' => 0, 'max' => 0, 'median' => 0];
        }

        return [
            'average' => round($salaries->avg(), 2),
            'min' => $salaries->min(),
            'max' => $salaries->max(),
            'median' => round($salaries->sort()->values()->get(floor($salaries->count() / 2)), 2),
            'total' => $salaries->sum()
        ];
    }

    private function getHireDateAnalysis($query): array
    {
        return $query->whereNotNull('hire_date')
            ->selectRaw('EXTRACT(YEAR FROM hire_date) as year, COUNT(*) as count')
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->toArray();
    }

    private function getGeographicDistribution($query): array
    {
        return $query->whereNotNull('country')
            ->select('country', DB::raw('COUNT(*) as count'))
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    private function getErrorTypeBreakdown(Carbon $start = null, Carbon $end = null): array
    {
        $query = ImportError::query();
        
        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }
        
        return $query->select('error_type', DB::raw('COUNT(*) as count'))
            ->groupBy('error_type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    private function getTopErrors(Carbon $start, Carbon $end): array
    {
        return ImportError::whereBetween('created_at', [$start, $end])
            ->select('error_message', DB::raw('COUNT(*) as count'))
            ->groupBy('error_message')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getErrorTrends(Carbon $start, Carbon $end): array
    {
        return ImportError::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getJobsWithMostErrors(Carbon $start, Carbon $end): array
    {
        return ImportError::whereBetween('created_at', [$start, $end])
            ->select('import_job_id', DB::raw('COUNT(*) as error_count'))
            ->groupBy('import_job_id')
            ->orderBy('error_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getAverageErrorsPerJob(Carbon $start, Carbon $end): float
    {
        $totalErrors = ImportError::whereBetween('created_at', [$start, $end])->count();
        $jobsWithErrors = ImportError::whereBetween('created_at', [$start, $end])
            ->distinct('import_job_id')
            ->count('import_job_id');
        
        return $jobsWithErrors > 0 ? round($totalErrors / $jobsWithErrors, 2) : 0;
    }

    private function getAverageProcessingTime(Carbon $start = null, Carbon $end = null): ?float
    {
        $query = ImportJob::where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('created_at');
            
        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }
        
        return $query->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - created_at))) as avg_time')
            ->value('avg_time');
    }

    private function getFastestImport(): ?array
    {
        return ImportJob::where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('created_at')
            ->selectRaw('*, EXTRACT(EPOCH FROM (completed_at - created_at)) as processing_time')
            ->orderBy('processing_time')
            ->first()
            ?->toArray();
    }

    private function getLargestImport(): ?array
    {
        return ImportJob::orderBy('total_rows', 'desc')
            ->first()
            ?->toArray();
    }

    private function getSuccessRate(Carbon $start = null, Carbon $end = null): float
    {
        $query = ImportJob::query();
        
        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }
        
        $totalRows = $query->sum('total_rows');
        $successfulRows = $query->sum('successful_rows');
        
        return $totalRows > 0 ? round(($successfulRows / $totalRows) * 100, 2) : 0;
    }

    private function getFastestProcessingTime(Carbon $start, Carbon $end): ?float
    {
        return ImportJob::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('created_at')
            ->selectRaw('MIN(EXTRACT(EPOCH FROM (completed_at - created_at))) as min_time')
            ->value('min_time');
    }

    private function getSlowestProcessingTime(Carbon $start, Carbon $end): ?float
    {
        return ImportJob::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('created_at')
            ->selectRaw('MAX(EXTRACT(EPOCH FROM (completed_at - created_at))) as max_time')
            ->value('max_time');
    }

    private function getTotalProcessingTime(Carbon $start, Carbon $end): float
    {
        return ImportJob::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('created_at')
            ->selectRaw('SUM(EXTRACT(EPOCH FROM (completed_at - created_at))) as total_time')
            ->value('total_time') ?? 0;
    }

    private function getAverageRowsPerMinute(Carbon $start, Carbon $end): float
    {
        $totalRows = ImportJob::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->sum('total_rows');
            
        $totalTime = $this->getTotalProcessingTime($start, $end);
        
        return $totalTime > 0 ? round(($totalRows / $totalTime) * 60, 2) : 0;
    }

    private function getPeakThroughput(Carbon $start, Carbon $end): array
    {
        $peakJob = ImportJob::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('created_at')
            ->selectRaw('*, EXTRACT(EPOCH FROM (completed_at - created_at)) as processing_time, (total_rows / EXTRACT(EPOCH FROM (completed_at - created_at))) * 60 as rows_per_minute')
            ->orderBy('rows_per_minute', 'desc')
            ->first();
            
        return $peakJob ? $peakJob->toArray() : [];
    }

    private function getTotalRowsProcessed(Carbon $start, Carbon $end): int
    {
        return ImportJob::whereBetween('created_at', [$start, $end])
            ->sum('total_rows');
    }

    private function getAverageSuccessRate(Carbon $start, Carbon $end): float
    {
        $jobs = ImportJob::whereBetween('created_at', [$start, $end])
            ->where('total_rows', '>', 0)
            ->get();
            
        if ($jobs->isEmpty()) {
            return 0;
        }
        
        $totalSuccessRate = $jobs->sum(function ($job) {
            return ($job->successful_rows / $job->total_rows) * 100;
        });
        
        return round($totalSuccessRate / $jobs->count(), 2);
    }

    private function getJobsWith100PercentSuccess(Carbon $start, Carbon $end): int
    {
        return ImportJob::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->whereRaw('successful_rows = total_rows')
            ->count();
    }

    private function getAverageFileSize(Carbon $start, Carbon $end): float
    {
        return ImportJob::whereBetween('created_at', [$start, $end])
            ->whereNotNull('file_size')
            ->avg('file_size') ?? 0;
    }

    private function getLargestFileProcessed(Carbon $start, Carbon $end): ?array
    {
        return ImportJob::whereBetween('created_at', [$start, $end])
            ->whereNotNull('file_size')
            ->orderBy('file_size', 'desc')
            ->first()
            ?->toArray();
    }

    private function getTotalDataProcessed(Carbon $start, Carbon $end): int
    {
        return ImportJob::whereBetween('created_at', [$start, $end])
            ->whereNotNull('file_size')
            ->sum('file_size');
    }

    private function getTrendData(string $metric, Carbon $start, Carbon $end): array
    {
        switch ($metric) {
            case 'imports':
                return $this->getDailyImportBreakdown($start, $end);
            case 'employees':
                return Employee::whereBetween('created_at', [$start, $end])
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->toArray();
            case 'errors':
                return $this->getErrorTrends($start, $end);
            default:
                return [];
        }
    }

    private function getTrendAnalysis(string $metric, Carbon $start, Carbon $end): array
    {
        $data = $this->getTrendData($metric, $start, $end);
        
        if (empty($data)) {
            return ['trend' => 'stable', 'change_percentage' => 0];
        }
        
        $firstHalf = array_slice($data, 0, ceil(count($data) / 2));
        $secondHalf = array_slice($data, ceil(count($data) / 2));
        
        $firstHalfAvg = array_sum(array_column($firstHalf, 'count')) / count($firstHalf);
        $secondHalfAvg = array_sum(array_column($secondHalf, 'count')) / count($secondHalf);
        
        $changePercentage = $firstHalfAvg > 0 
            ? round((($secondHalfAvg - $firstHalfAvg) / $firstHalfAvg) * 100, 2)
            : 0;
            
        $trend = $changePercentage > 5 ? 'increasing' : 
                ($changePercentage < -5 ? 'decreasing' : 'stable');
        
        return [
            'trend' => $trend,
            'change_percentage' => $changePercentage,
            'first_half_average' => round($firstHalfAvg, 2),
            'second_half_average' => round($secondHalfAvg, 2)
        ];
    }
}
