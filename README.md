# BulkBridge - Bulk Employee Data Import System

A robust, full-stack application for bulk importing employee data from CSV files with real-time progress tracking, comprehensive validation, and error handling.

## 🚀 Quick Navigation

| Setup Option | Description | Documentation |
|--------------|-------------|---------------|
| **🏠 Local Development** | **Recommended** - Run without Docker | [**LOCAL_DEVELOPMENT.md**](LOCAL_DEVELOPMENT.md) |
| **🐳 Docker Setup** | Containerized deployment | [Docker Setup](#option-2-docker-setup-alternative) |
| **⚙️ Configuration** | Environment & deployment configs | [**CONFIGURATION_GUIDE.md**](CONFIGURATION_GUIDE.md) |

> **💡 New to BulkBridge?** Start with [**LOCAL_DEVELOPMENT.md**](LOCAL_DEVELOPMENT.md) for the easiest setup experience.

## 🎯 Getting Started

**Choose your setup method:**

1. **🏠 [Local Development](LOCAL_DEVELOPMENT.md)** - **Recommended for beginners**
   - No Docker required
   - Faster development cycle
   - Easier debugging
   - Direct access to logs

2. **🐳 [Docker Setup](#option-2-docker-setup-alternative)** - For containerized deployment
   - Isolated environment
   - Production-like setup
   - Consistent across machines

## 🏗️ Architecture

- **Backend**: Laravel 12 (PHP 8.4) with PostgreSQL
- **Frontend**: React 18 with modern UI components
- **Queue System**: Redis-based job processing
- **Database**: PostgreSQL with optimized schema
- **Containerization**: Docker & Docker Compose
- **Real-time Updates**: Redis polling for progress tracking

## 📋 Prerequisites

### For Local Development (Recommended)
- **PHP 8.2+** with extensions: `pdo_pgsql`, `mbstring`, `zip`, `gd`, `redis`
- **Composer** (PHP dependency manager)
- **Node.js 18+** and **npm**
- **PostgreSQL 13+** (running locally)
- **Redis** (for queue processing)
- **Git** for version control

### For Docker Setup (Alternative)
- **Docker** (v20.10+) and **Docker Compose** (v2.0+)
- **PostgreSQL** (v13+) running locally
- **Git** for version control


## 🚀 Quick Start

### Option 1: Local Development (Recommended)

**Start here for the easiest setup without Docker dependencies.**

#### 1. Prerequisites
- PHP 8.2+ with extensions: `pdo_pgsql`, `mbstring`, `zip`, `gd`, `redis`
- Composer
- Node.js 18+ and npm
- PostgreSQL 13+ (running locally)
- Redis (for queue processing)

#### 2. Quick Start
```bash
# Clone and setup
git clone <repository-url>
cd BulkBridge

# Make the local setup script executable
chmod +x run-local.sh

# Start all services
./run-local.sh start
```

#### 3. Access the Application
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000/api
- **Health Check**: http://localhost:8000/health

#### 4. Service Management
```bash
# Stop all services
./run-local.sh stop

# Restart all services
./run-local.sh restart

# Start only backend
./run-local.sh backend

# Start only frontend
./run-local.sh frontend
```

> 📖 **📋 Complete Local Setup Guide**: See [**LOCAL_DEVELOPMENT.md**](LOCAL_DEVELOPMENT.md) for detailed instructions, troubleshooting, and configuration options.

### Option 2: Docker Setup (Alternative)

If you prefer Docker or need containerized deployment:

#### 1. Clone the Repository
```bash
git clone <repository-url>
cd BulkBridge
```

#### 2. Database Setup
Create the PostgreSQL database:
```bash
# Connect to PostgreSQL
psql -U {your postgres user}

# Create the database
CREATE DATABASE bulkbridge;

# Exit PostgreSQL
\q
```

#### 3. Start with Docker
```bash
# Make the startup script executable
chmod +x docker-setup.sh

# Start all services
./docker-setup.sh
```

#### 4. Access the Application
- **Frontend**: http://localhost
- **Backend API**: http://localhost/api
- **Health Check**: http://localhost/health
- **Database**: localhost:5432 (PostgreSQL)
- **Redis**: localhost:6379

## 🔧 Detailed Setup Guide

### Environment Configuration

The application automatically configures itself to use your local PostgreSQL database with the `{your postgress user}` user. The Docker setup includes:

- **Backend**: Laravel application with all required PHP extensions
- **Frontend**: React development server with hot reload
- **Queue Worker**: Optimized for bulk processing
- **Redis**: For caching and job management
- **Nginx**: Reverse proxy for routing requests

### Database Configuration

The system connects to your local PostgreSQL instance:
- **Host**: `host.docker.internal`
- **Port**: `5432`
- **Database**: `bulkbridge`
- **Username**: `{your postgess user}`
- **Password**: (empty)

### Service Ports

| Service | Port | Description |
|---------|------|-------------|
| Frontend | 80 | React app (via Nginx) |
| Backend | 80/api | Laravel API (via Nginx) |
| PostgreSQL | 5432 | Database (host machine) |
| Redis | 6379 | Cache and queue management |
| Nginx | 80 | Reverse proxy |

## 📊 Using the Application

### 1. Upload CSV Files
- Navigate to the **Upload** page
- Drag and drop or select CSV files
- Supported format: CSV with employee data columns
- Maximum file size: 100MB (configurable)

### 2. Monitor Progress
- Go to the **Monitor** page to see active jobs
- Real-time progress updates every 3 seconds
- View processing statistics and status

### 3. View History
- Check the **History** page for completed imports
- Review success/failure rates
- Access detailed error reports

### 4. CSV Format Requirements

Your CSV file should include these columns:
```csv
first_name,last_name,email,phone,department,position,salary,hire_date,country_code,currency
John,Doe,john.doe@example.com,+1234567890,Engineering,Developer,75000,2023-01-15,US,USD
Jane,Smith,jane.smith@example.com,+1234567891,Marketing,Manager,85000,2023-02-01,US,USD
```

## 🛠️ Development Commands

### Docker Service Management
```bash
# Start all services
./docker-setup.sh

# Stop all services
docker compose down

# Start services in background
docker compose up -d

# View logs (real-time)
docker compose logs -f

# View logs for specific service
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f postgres
docker compose logs -f redis

# Restart a specific service
docker compose restart backend
docker compose restart frontend

# Rebuild and start (after code changes)
docker compose down
docker compose up -d --build

# Check service status
docker compose ps

# Access container shell
docker compose exec backend bash
docker compose exec frontend sh
docker compose exec postgres psql -U bulkbridge -d bulkbridge
```

### Backend Commands
```bash
# Run migrations
docker compose exec backend php artisan migrate

# Run migrations with force (for production)
docker compose exec backend php artisan migrate --force

# Clear caches
docker compose exec backend php artisan cache:clear
docker compose exec backend php artisan config:clear
docker compose exec backend php artisan route:clear
docker compose exec backend php artisan view:clear

# Run tests
docker compose exec backend php artisan test

# Access Laravel Tinker
docker compose exec backend php artisan tinker

# Check queue status
docker compose exec backend php artisan queue:work --once
docker compose exec backend php artisan queue:failed
docker compose exec backend php artisan queue:retry all
```

### Frontend Commands
```bash
# Install dependencies
docker compose exec frontend npm install

# Run tests
docker compose exec frontend npm test

# Build for production
docker compose exec frontend npm run build

# Start development server (if not using Docker)
cd fe && npm start
```

### Database Commands
```bash
# Connect to PostgreSQL
docker compose exec postgres psql -U bulkbridge -d bulkbridge

# Check database connection from backend
docker compose exec backend php artisan tinker
>>> DB::connection()->getPdo()

# Check import jobs
>>> App\Models\ImportJob::count()

# Check employees
>>> App\Models\Employee::count()

# Check import errors
>>> App\Models\ImportError::count()
```

## 🔍 Monitoring and Debugging

### Service Health Checks
```bash
# Check if all services are running
docker compose ps

# Test application endpoints
curl http://localhost/health
curl http://localhost/api/health

# Check service logs
docker compose logs --tail=50 backend
docker compose logs --tail=50 frontend
docker compose logs --tail=50 postgres
docker compose logs --tail=50 redis
```

### Queue Worker Status
```bash
# Check queue worker logs
docker compose logs backend | grep "queue"

# Check failed jobs
docker compose exec backend php artisan queue:failed

# Retry failed jobs
docker compose exec backend php artisan queue:retry all

# Process one job manually
docker compose exec backend php artisan queue:work --once
```

### Database Monitoring
```bash
# Check import jobs
docker compose exec backend php artisan tinker
>>> App\Models\ImportJob::latest()->first()

# Check employee count
>>> App\Models\Employee::count()

# Check import errors
>>> App\Models\ImportError::count()

# Check database connection
>>> DB::connection()->getPdo()

# View recent import jobs
>>> App\Models\ImportJob::orderBy('created_at', 'desc')->take(5)->get()
```

### Redis Monitoring
```bash
# Connect to Redis
docker compose exec redis redis-cli

# Check Redis info
INFO

# Check queue length
LLEN queues:imports

# Monitor Redis commands
MONITOR

# Check Redis keys
KEYS *

# Check import progress
HGETALL import:progress:*
```

## 📈 Performance Optimization

### For Large Files (10,000+ rows)
- The system automatically chunks processing into batches of 1,000 rows
- Memory management includes garbage collection
- Queue workers auto-restart after processing 10 jobs or 5 minutes

### Database Optimization
- Indexes on frequently queried columns
- Chunked database operations
- Connection pooling for high concurrency

### Monitoring Performance
```bash
# Check system resources
docker stats

# Monitor queue processing
docker compose logs queue --tail=50

# Check database performance
docker compose exec backend php artisan tinker
>>> DB::select('SELECT * FROM pg_stat_activity');
```

## 🚨 Troubleshooting

### Common Issues

#### Services Not Starting
```bash
# Check if ports are in use
lsof -i :80    # Nginx
lsof -i :5432  # PostgreSQL
lsof -i :6379  # Redis

# Stop conflicting services
sudo brew services stop redis  # macOS
sudo systemctl stop redis      # Linux

# Force restart all services
docker compose down
docker compose up -d --force-recreate
```

#### Docker Issues - Switch to Local Development
If Docker continues to fail, use the local development setup:

```bash
# Stop Docker services
docker compose down

# Use local development instead
./run-local.sh start
```

**Common Docker Issues:**
- Port conflicts (Redis, PostgreSQL already running)
- Docker daemon not running
- Insufficient disk space
- Memory constraints
- Network issues

**Local Development Benefits:**
- No Docker overhead
- Direct access to logs
- Easier debugging
- Faster development cycle

#### Queue Worker Not Processing Jobs
```bash
# Check if queue worker is running
docker compose ps backend

# Check queue worker logs
docker compose logs backend | grep -i queue

# Restart backend service
docker compose restart backend

# Check Redis connection
docker compose exec backend php artisan tinker
>>> Redis::ping()

# Manually process a job
docker compose exec backend php artisan queue:work --once
```

#### Database Connection Issues
```bash
# Verify PostgreSQL is running locally
psql -U abedkiloo -d bulkbridge -c "SELECT 1;"

# Check database configuration
docker compose exec backend php artisan tinker
>>> config('database.connections.pgsql')

# Test database connection
>>> DB::connection()->getPdo()

# Run migrations manually
docker compose exec backend php artisan migrate --force
```

#### Frontend Not Loading
```bash
# Check frontend container
docker compose logs frontend

# Restart frontend
docker compose restart frontend

# Check if port 80 is available
lsof -i :80

# Test frontend directly
curl http://localhost
```

#### Redis Connection Issues
```bash
# Check Redis container
docker compose logs redis

# Test Redis connection
docker compose exec redis redis-cli ping

# Check Redis from backend
docker compose exec backend php artisan tinker
>>> Redis::ping()
>>> Redis::info()
```

#### Import Jobs Not Processing
```bash
# Check import job status
docker compose exec backend php artisan tinker
>>> App\Models\ImportJob::latest()->first()

# Check for failed jobs
>>> App\Models\ImportError::count()

# Check queue status
>>> Redis::llen('queues:imports')

# Manually dispatch a job
>>> App\Jobs\ProcessBulkImportJob::dispatch('job-uuid-here')
```

### Log Locations
- **Backend logs**: `docker compose logs backend`
- **Frontend logs**: `docker compose logs frontend`
- **PostgreSQL logs**: `docker compose logs postgres`
- **Redis logs**: `docker compose logs redis`
- **Nginx logs**: `docker compose logs nginx`
- **Laravel logs**: `docker compose exec backend cat storage/logs/laravel.log`


## 📚 API Documentation

### Import Endpoints (V1 API)
- `POST /api/v1/imports` - Upload CSV file
- `GET /api/v1/imports` - List all import jobs
- `GET /api/v1/imports/{jobId}` - Get job details
- `GET /api/v1/imports/{jobId}/status` - Get job status
- `GET /api/v1/imports/{jobId}/stream` - Stream job progress (SSE)
- `POST /api/v1/imports/{jobId}/retry` - Retry failed job
- `POST /api/v1/imports/{jobId}/retry-failed` - Retry only failed rows
- `POST /api/v1/imports/{jobId}/cancel` - Cancel job

### Health Check Endpoints
- `GET /health` - Application health check
- `GET /api/health` - API health check

### Testing API Endpoints
```bash
# Test health check
curl http://localhost/health

# Test API health
curl http://localhost/api/health

# List import jobs
curl http://localhost/api/v1/imports

# Get job status
curl http://localhost/api/v1/imports/{job-id}/status

# Upload a CSV file
curl -X POST -F "file=@sample.csv" http://localhost/api/v1/imports
```

## 🧪 Testing the Application

### 1. Basic Functionality Test
```bash
# Start the application
./docker-setup.sh

# Wait for services to be ready (30-60 seconds)
sleep 60

# Test health endpoints
curl http://localhost/health
curl http://localhost/api/health

# Check service status
docker compose ps
```

### 2. Database Connection Test
```bash
# Test database connection
docker compose exec backend php artisan tinker
>>> DB::connection()->getPdo()
>>> App\Models\ImportJob::count()

# Check if migrations ran
>>> Schema::hasTable('import_jobs')
>>> Schema::hasTable('employees')
>>> Schema::hasTable('import_errors')
```

### 3. CSV Upload Test
```bash
# Create a test CSV file
cat > test_employees.csv << EOF
employee_number,first_name,last_name,email,department,salary,currency,country_code,start_date
EMP001,John,Doe,john.doe@example.com,Engineering,75000,USD,US,2023-01-15
EMP002,Jane,Smith,jane.smith@example.com,Marketing,65000,USD,US,2023-02-01
EMP003,Bob,Johnson,bob.johnson@example.com,Sales,55000,USD,US,2023-03-01
EOF

# Upload the CSV file
curl -X POST -F "file=@test_employees.csv" http://localhost/api/v1/imports

# Check the response for job ID
```

### 4. Job Processing Test
```bash
# Get the job ID from the upload response, then:
JOB_ID="your-job-id-here"

# Check job status
curl http://localhost/api/v1/imports/$JOB_ID/status

# Monitor job progress
curl http://localhost/api/v1/imports/$JOB_ID/stream

# Check job details
curl http://localhost/api/v1/imports/$JOB_ID
```

### 5. Frontend Testing
```bash
# Open the application in browser
open http://localhost  # macOS
xdg-open http://localhost  # Linux
start http://localhost  # Windows

# Test the following:
# 1. Upload page - drag and drop CSV file
# 2. Monitor page - watch real-time progress
# 3. Details page - view job history and details
```

### 6. Queue Processing Test
```bash
# Check if queue worker is processing jobs
docker compose logs backend | grep -i "processing"

# Check Redis queue
docker compose exec redis redis-cli
> LLEN queues:imports
> KEYS *

# Check for failed jobs
docker compose exec backend php artisan queue:failed
```

### 7. Error Handling Test
```bash
# Create a CSV with invalid data
cat > invalid_employees.csv << EOF
employee_number,first_name,last_name,email,department,salary,currency,country_code,start_date
EMP001,John,,invalid-email,Engineering,invalid-salary,USD,US,invalid-date
EMP002,,Smith,jane.smith@example.com,,65000,USD,US,2023-02-01
EOF

# Upload invalid CSV
curl -X POST -F "file=@invalid_employees.csv" http://localhost/api/v1/imports

# Check for validation errors
docker compose exec backend php artisan tinker
>>> App\Models\ImportError::latest()->first()
```

### 8. Performance Test
```bash
# Create a large CSV file (1000+ rows)
python3 -c "
import csv
with open('large_test.csv', 'w', newline='') as f:
    writer = csv.writer(f)
    writer.writerow(['employee_number','first_name','last_name','email','department','salary','currency','country_code','start_date'])
    for i in range(1000):
        writer.writerow([f'EMP{i:04d}', f'Employee{i}', f'Last{i}', f'emp{i}@example.com', 'Engineering', 50000 + i, 'USD', 'US', '2023-01-01'])
"

# Upload large file and monitor performance
curl -X POST -F "file=@large_test.csv" http://localhost/api/v1/imports

# Monitor system resources
docker stats
```

### 9. Retry Functionality Test
```bash
# Get a job with failed rows
JOB_ID="your-failed-job-id"

# Retry failed rows
curl -X POST http://localhost/api/v1/imports/$JOB_ID/retry-failed

# Check the new retry job
curl http://localhost/api/v1/imports
```

### 10. Cleanup Test
```bash
# Stop all services
docker compose down

# Remove volumes (optional - this will delete all data)
docker compose down -v

# Restart and verify clean state
./docker-setup.sh
```

## 🆘 Need Help?

### Quick Links
- **🏠 [Local Development Setup](LOCAL_DEVELOPMENT.md)** - Start here for easiest setup
- **⚙️ [Configuration Guide](CONFIGURATION_GUIDE.md)** - Environment setup and troubleshooting
- **🐳 [Docker Setup](#option-2-docker-setup-alternative)** - Containerized deployment

### Common Issues
- **Docker problems?** → Switch to [Local Development](LOCAL_DEVELOPMENT.md)
- **Configuration issues?** → Check [Configuration Guide](CONFIGURATION_GUIDE.md)
- **Database connection?** → See [Local Development Guide](LOCAL_DEVELOPMENT.md#database-setup)

### Documentation
- **[🏠 Local Development Guide](LOCAL_DEVELOPMENT.md)** - Complete local setup instructions
- **[⚙️ Configuration Guide](CONFIGURATION_GUIDE.md)** - Environment and deployment configurations
- **[🐳 Docker Setup](#option-2-docker-setup-alternative)** - Containerized deployment guide

---

**BulkBridge** - Efficiently import and manage employee data at scale! 
