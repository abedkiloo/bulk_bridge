<?php

/**
 * Queue Status Checker
 * This script checks if there are pending jobs and manages queue workers
 */

// Get the directory where this script is located
$scriptDir = dirname(__FILE__);

require_once $scriptDir . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once $scriptDir . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

function checkQueueStatus() {
    try {
        // Check for pending jobs
        $pendingJobs = DB::table('jobs')->where('queue', 'imports')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        
        echo "Queue Status Check:\n";
        echo "==================\n";
        echo "Pending jobs: {$pendingJobs}\n";
        echo "Failed jobs: {$failedJobs}\n";
        
        if ($pendingJobs === 0) {
            echo "✅ No pending jobs - queue is idle\n";
            return true;
        } else {
            echo "🔄 {$pendingJobs} jobs pending - queue is active\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "❌ Error checking queue status: " . $e->getMessage() . "\n";
        return false;
    }
}

function stopIdleWorkers() {
    try {
        // Check if there are any queue workers running
        $output = shell_exec('ps aux | grep "php artisan queue:work" | grep -v grep');
        
        if (empty($output)) {
            echo "No queue workers running\n";
            return;
        }
        
        echo "Stopping idle queue workers...\n";
        shell_exec('pkill -f "php artisan queue:work"');
        echo "Queue workers stopped\n";
        
    } catch (Exception $e) {
        echo "Error stopping workers: " . $e->getMessage() . "\n";
    }
}

// Main execution
$isIdle = checkQueueStatus();

if ($isIdle) {
    echo "\nQueue is idle. Stopping workers to save resources...\n";
    stopIdleWorkers();
} else {
    echo "\nQueue has pending jobs. Workers should continue running.\n";
}

echo "\nDone.\n";
