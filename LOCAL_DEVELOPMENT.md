# BulkBridge - Local Development Guide (Without Docker)

This guide shows how to run BulkBridge locally without Docker as a fallback option.

## 📋 Prerequisites

### Required Software
- **PHP 8.2+** with extensions: `pdo_pgsql`, `mbstring`, `zip`, `gd`, `redis`
- **Composer** (PHP dependency manager)
- **Node.js 18+** and **npm**
- **PostgreSQL 13+** (running locally)
- **Redis** (for queue processing)

### System Requirements
- **RAM**: Minimum 4GB (8GB recommended)
- **Storage**: 2GB free space
- **OS**: macOS, Linux, or Windows with WSL2

## 🚀 Quick Start

### 1. Clone and Setup
```bash
git clone <repository-url>
cd BulkBridge

# Make the local setup script executable
chmod +x run-local.sh

# Run the setup and start all services
./run-local.sh start
```

### 2. Manual Setup (Alternative)

If the script doesn't work, follow these manual steps:

#### Backend Setup
```bash
cd be

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Update database configuration in .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=bulkbridge
# DB_USERNAME=abedkiloo
# DB_PASSWORD=

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Clear caches
php artisan config:clear
php artisan cache:clear
```

#### Frontend Setup
```bash
cd fe

# Install Node.js dependencies
npm install
```

#### Start Services
```bash
# Terminal 1: Start Redis
redis-server

# Terminal 2: Start Laravel server
cd be
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 3: Start queue worker
cd be
php artisan queue:work

# Terminal 4: Start React server
cd fe
npm start
```

## 🔧 Configuration Changes

### Backend Configuration (`be/.env`)

Update these key settings for local development:

```env
# Application
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bulkbridge
DB_USERNAME=abedkiloo
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Queue
QUEUE_CONNECTION=redis

# Cache
CACHE_STORE=redis

# Session
SESSION_DRIVER=database
```

### Frontend Configuration (`fe/package.json`)

The React app is configured to proxy API requests to the Laravel backend:

```json
{
  "proxy": "http://localhost:8000"
}
```

### API Configuration (`fe/src/services/api.js`)

Update the API base URL if needed:

```javascript
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';
```

## 🛠️ Service Management

### Using the Script

```bash
# Start all services
./run-local.sh start

# Stop all services
./run-local.sh stop

# Restart all services
./run-local.sh restart

# Setup only (no start)
./run-local.sh setup

# Start only backend
./run-local.sh backend

# Start only frontend
./run-local.sh frontend
```

### Manual Service Management

#### Start Services
```bash
# Redis
redis-server --daemonize yes

# Laravel (Terminal 1)
cd be && php artisan serve --host=0.0.0.0 --port=8000

# Queue Worker (Terminal 2)
cd be && php artisan queue:work --daemon

# React (Terminal 3)
cd fe && npm start
```

#### Stop Services
```bash
# Stop Redis
redis-cli shutdown

# Stop Laravel (Ctrl+C in terminal)

# Stop Queue Worker (Ctrl+C in terminal)

# Stop React (Ctrl+C in terminal)
```

## 🔍 Troubleshooting

### Common Issues

#### 1. PHP Extensions Missing
```bash
# Install required PHP extensions
# Ubuntu/Debian
sudo apt-get install php-pgsql php-mbstring php-zip php-gd php-redis

# macOS (with Homebrew)
brew install php@8.2
brew install php-redis

# Check installed extensions
php -m | grep -E "(pdo_pgsql|mbstring|zip|gd|redis)"
```

#### 2. Database Connection Issues
```bash
# Test PostgreSQL connection
psql -U abedkiloo -d bulkbridge -c "SELECT 1;"

# Check if database exists
psql -U abedkiloo -l | grep bulkbridge

# Create database if needed
psql -U abedkiloo -c "CREATE DATABASE bulkbridge;"
```

#### 3. Redis Connection Issues
```bash
# Test Redis connection
redis-cli ping

# Check Redis status
redis-cli info server

# Start Redis if not running
redis-server --daemonize yes
```

#### 4. Port Conflicts
```bash
# Check what's using ports
lsof -i :8000  # Laravel
lsof -i :3000  # React
lsof -i :6379  # Redis

# Kill processes if needed
kill -9 <PID>
```

#### 5. Permission Issues
```bash
# Fix Laravel permissions
cd be
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Fix Composer permissions
sudo chown -R $USER:$USER ~/.composer
```

### Log Locations

- **Laravel logs**: `be/storage/logs/laravel.log`
- **React logs**: Check terminal where `npm start` is running
- **Redis logs**: Check system logs or Redis configuration
- **PostgreSQL logs**: Check system logs or PostgreSQL configuration

## 📊 Testing the Application

### 1. Health Checks
```bash
# Test Laravel API
curl http://localhost:8000/health

# Test React app
curl http://localhost:3000
```

### 2. Database Testing
```bash
# Connect to database
psql -U abedkiloo -d bulkbridge

# Check tables
\dt

# Check import jobs
SELECT COUNT(*) FROM import_jobs;

# Check employees
SELECT COUNT(*) FROM employees;
```

### 3. Queue Testing
```bash
# Check queue status
cd be && php artisan queue:work --once

# Check failed jobs
cd be && php artisan queue:failed

# Retry failed jobs
cd be && php artisan queue:retry all
```

## 🔄 Development Workflow

### Making Changes

1. **Backend Changes**:
   - Edit PHP files in `be/app/`
   - Clear caches: `php artisan config:clear && php artisan cache:clear`
   - Restart queue worker if needed

2. **Frontend Changes**:
   - Edit React files in `fe/src/`
   - Changes auto-reload with `npm start`

3. **Database Changes**:
   - Create migration: `php artisan make:migration create_table_name`
   - Run migration: `php artisan migrate`
   - Rollback if needed: `php artisan migrate:rollback`

### Hot Reloading

- **React**: Automatic with `npm start`
- **Laravel**: Manual restart required for PHP changes
- **Queue Worker**: Restart after code changes

## 🚀 Production Considerations

### Environment Variables
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Use strong database credentials
DB_PASSWORD=your_secure_password

# Use Redis with password
REDIS_PASSWORD=your_redis_password

# Use file-based sessions for production
SESSION_DRIVER=file
```

### Performance Optimization
```bash
# Optimize Composer autoloader
composer install --optimize-autoloader --no-dev

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build React for production
cd fe && npm run build
```

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://reactjs.org/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Redis Documentation](https://redis.io/documentation)

## 🆘 Getting Help

If you encounter issues:

1. Check the troubleshooting section above
2. Review the logs in `be/storage/logs/laravel.log`
3. Ensure all prerequisites are installed
4. Verify database and Redis connections
5. Check port availability

---

**Note**: This local development setup is intended for development and testing. For production deployment, use Docker or a proper server setup with optimized configurations.
