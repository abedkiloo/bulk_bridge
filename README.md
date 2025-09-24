# BulkBridge - Bulk Employee Data Import System

A robust, full-stack application for bulk importing employee data from CSV files with real-time progress tracking, comprehensive validation, and error handling.

## 🏗️ Architecture

- **Backend**: Laravel 12 (PHP 8.2) with PostgreSQL
- **Frontend**: React 18 with modern UI components
- **Queue System**: Redis-based job processing
- **Database**: PostgreSQL with optimized schema
- **Containerization**: Docker & Docker Compose
- **Real-time Updates**: Redis polling for progress tracking

## 📋 Prerequisites

Before setting up the application, ensure you have the following installed:

### Required Software
- **Docker** (v20.10+) and **Docker Compose** (v2.0+)
- **PostgreSQL** (v13+) running locally
- **Git** for version control

### System Requirements
- **RAM**: Minimum 4GB (8GB recommended for large imports)
- **Storage**: 2GB free space
- **OS**: macOS, Linux, or Windows with WSL2

## 🚀 Quick Start

### 1. Clone the Repository
```bash
git clone <repository-url>
cd BulkBridge
```

### 2. Database Setup
Create the PostgreSQL database:
```bash
# Connect to PostgreSQL
psql -U abedkiloo

# Create the database
CREATE DATABASE bulkbridge;

# Exit PostgreSQL
\q
```

### 3. Start the Application
```bash
# Make the startup script executable
chmod +x docker.sh

# Start all services
./docker.sh
```

### 4. Access the Application
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000/api
- **Database**: localhost:5432 (PostgreSQL)
- **Redis**: localhost:6380

## 🔧 Detailed Setup Guide

### Environment Configuration

The application automatically configures itself to use your local PostgreSQL database with the `abedkiloo` user. The Docker setup includes:

- **Backend**: Laravel application with all required PHP extensions
- **Frontend**: React development server with hot reload
- **Queue Worker**: Optimized for bulk processing
- **Redis**: For caching and job management

### Database Configuration

The system connects to your local PostgreSQL instance:
- **Host**: `host.docker.internal`
- **Port**: `5432`
- **Database**: `bulkbridge`
- **Username**: `abedkiloo`
- **Password**: (empty)

### Service Ports

| Service | Port | Description |
|---------|------|-------------|
| Frontend | 3000 | React development server |
| Backend | 8000 | Laravel API server |
| PostgreSQL | 5432 | Database (host machine) |
| Redis | 6380 | Cache and queue management |

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

### Docker Commands
```bash
# Start all services
./docker.sh

# Stop all services
docker compose down

# View logs
docker compose logs -f [service_name]

# Restart a specific service
docker compose restart [service_name]

# Access container shell
docker compose exec [service_name] /bin/sh
```

### Backend Commands
```bash
# Run migrations
docker compose exec backend php artisan migrate

# Clear caches
docker compose exec backend php artisan cache:clear
docker compose exec backend php artisan config:clear

# Run tests
docker compose exec backend php artisan test

# Access Laravel Tinker
docker compose exec backend php artisan tinker
```

### Frontend Commands
```bash
# Install dependencies
docker compose exec frontend npm install

# Run tests
docker compose exec frontend npm test

# Build for production
docker compose exec frontend npm run build
```

## 🔍 Monitoring and Debugging

### Queue Worker Status
```bash
# Check queue worker logs
docker compose logs queue

# Check failed jobs
docker compose exec backend php artisan queue:failed

# Retry failed jobs
docker compose exec backend php artisan queue:retry all
```

### Database Queries
```bash
# Check import jobs
docker compose exec backend php artisan tinker
>>> App\Models\ImportJob::latest()->first()

# Check employee count
>>> App\Models\Employee::count()
```

### Redis Monitoring
```bash
# Connect to Redis
docker compose exec redis redis-cli -a redis_password

# Check queue length
LLEN queues:imports

# Monitor Redis commands
MONITOR
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

#### Queue Worker Not Processing Jobs
```bash
# Check if queue worker is running
docker compose ps queue

# Restart queue worker
docker compose restart queue

# Check Redis connection
docker compose exec backend php artisan tinker
>>> Redis::ping()
```

#### Database Connection Issues
```bash
# Verify PostgreSQL is running
psql -U abedkiloo -d bulkbridge -c "SELECT 1;"

# Check database configuration
docker compose exec backend php artisan tinker
>>> config('database.connections.pgsql')
```

#### Frontend Not Loading
```bash
# Check frontend container
docker compose logs frontend

# Restart frontend
docker compose restart frontend

# Check if port 3000 is available
lsof -i :3000
```

### Log Locations
- **Backend logs**: `be/storage/logs/laravel.log`
- **Queue logs**: `docker compose logs queue`
- **Frontend logs**: `docker compose logs frontend`
- **Database logs**: Check PostgreSQL logs on host system

## 🔒 Security Considerations

### Production Deployment
- Change default Redis password
- Use environment variables for sensitive data
- Enable HTTPS for production
- Configure proper database permissions
- Set up firewall rules

### Data Protection
- All file uploads are validated
- SQL injection protection via Eloquent ORM
- CSRF protection on all forms
- Input sanitization and validation

## 📚 API Documentation

### Import Endpoints
- `POST /api/import/upload` - Upload CSV file
- `GET /api/import/jobs` - List all import jobs
- `GET /api/import/job/{id}/status` - Get job status
- `POST /api/import/job/{id}/cancel` - Cancel job
- `POST /api/import/job/{id}/retry` - Retry failed job

### Employee Endpoints
- `GET /api/employees` - List employees
- `GET /api/employees/{id}` - Get employee details
- `POST /api/employees` - Create employee
- `PUT /api/employees/{id}` - Update employee
- `DELETE /api/employees/{id}` - Delete employee

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Check the troubleshooting section above
- Review the logs for error messages
- Create an issue in the repository
- Contact the development team

---

**BulkBridge** - Efficiently import and manage employee data at scale! 🚀
