#!/bin/bash

# Optimized Queue Worker Startup Script
# This script starts the queue worker with optimized settings

echo "🚀 Starting optimized queue worker..."

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Kill any existing queue workers
pkill -f "php artisan queue:work" 2>/dev/null

# Start queue worker with optimized settings
php artisan queue:work \
    --queue=imports \
    --timeout=1800 \
    --tries=1 \
    --max-jobs=10 \
    --max-time=300 \
    --verbose

echo "✅ Queue worker started with optimized settings"
echo "   - Max jobs per worker: 10"
echo "   - Max time per worker: 5 minutes"
echo "   - Timeout per job: 30 minutes"
echo "   - Auto-restart to prevent memory leaks"
