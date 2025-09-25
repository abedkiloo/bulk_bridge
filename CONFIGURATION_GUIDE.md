# BulkBridge - Configuration Guide

This guide explains how to configure BulkBridge for different environments and deployment scenarios.

## 🔧 Environment Configuration

### Backend Configuration (`be/.env`)

The backend uses Laravel's environment configuration system. Here are the key settings:

#### Application Settings
```env
APP_NAME=BulkBridge
APP_ENV=local                    # local, production, testing
APP_DEBUG=true                   # false for production
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000    # Update for production
```

#### Database Configuration
```env
# PostgreSQL Configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1               # localhost for local, IP for remote
DB_PORT=5432
DB_DATABASE=bulkbridge
DB_USERNAME=abedkiloo           # Your PostgreSQL username
DB_PASSWORD=                    # Your PostgreSQL password
```

#### Redis Configuration
```env
# Redis for caching and queues
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1           # localhost for local, IP for remote
REDIS_PASSWORD=null            # Set password for production
REDIS_PORT=6379
```

#### Queue Configuration
```env
# Queue processing
QUEUE_CONNECTION=redis          # Use redis for queue processing
CACHE_STORE=redis              # Use redis for caching
SESSION_DRIVER=database        # Use database for sessions
```

#### Import-Specific Settings
```env
# Import processing configuration
IMPORT_BATCH_SIZE=500          # Rows per batch
IMPORT_MAX_FILE_SIZE=20971520  # 20MB max file size
IMPORT_MAX_ROWS=50000          # Max rows per import
IMPORT_TIMEOUT=3600            # 1 hour timeout
REDIS_IMPORTS_DB=2             # Separate Redis DB for imports
```

### Frontend Configuration (`fe/package.json`)

The React frontend is configured to proxy API requests:

```json
{
  "name": "bulkbridge-frontend",
  "version": "0.1.0",
  "private": true,
  "proxy": "http://localhost:8000",
  "dependencies": {
    // ... dependencies
  },
  "scripts": {
    "start": "react-scripts start",
    "build": "react-scripts build",
    "test": "react-scripts test",
    "eject": "react-scripts eject"
  }
}
```

### API Configuration (`fe/src/services/api.js`)

Update the API base URL for different environments:

```javascript
// Development
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

// Production
const API_BASE_URL = process.env.REACT_APP_API_URL || 'https://yourdomain.com/api';
```

## 🐳 Docker Configuration

### Docker Compose (`docker-compose.yml`)

Key configuration points:

```yaml
services:
  backend:
    build: ./be
    ports:
      - "8000:8000"
    environment:
      - DB_HOST=host.docker.internal  # Connect to host PostgreSQL
      - REDIS_HOST=redis
    depends_on:
      - redis

  frontend:
    build: ./fe
    ports:
      - "3000:3000"
    environment:
      - REACT_APP_API_URL=http://localhost:8000/api

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  postgres:
    image: postgres:15-alpine
    ports:
      - "5432:5432"
    environment:
      - POSTGRES_DB=bulkbridge
      - POSTGRES_USER=bulkbridge
      - POSTGRES_PASSWORD=password
```

### Backend Dockerfile (`be/Dockerfile`)

Ensure PostgreSQL extension is installed:

```dockerfile
# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip
```

## 🏠 Local Development Configuration

### Prerequisites Check

Create a script to verify all requirements:

```bash
#!/bin/bash
# check-requirements.sh

echo "Checking BulkBridge requirements..."

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    echo "✅ PHP $PHP_VERSION found"
else
    echo "❌ PHP not found"
fi

# Check required PHP extensions
REQUIRED_EXTENSIONS=("pdo_pgsql" "mbstring" "zip" "gd" "redis")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "$ext"; then
        echo "✅ PHP extension $ext found"
    else
        echo "❌ PHP extension $ext missing"
    fi
done

# Check Composer
if command -v composer &> /dev/null; then
    echo "✅ Composer found"
else
    echo "❌ Composer not found"
fi

# Check Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo "✅ Node.js $NODE_VERSION found"
else
    echo "❌ Node.js not found"
fi

# Check PostgreSQL
if command -v psql &> /dev/null; then
    echo "✅ PostgreSQL found"
else
    echo "❌ PostgreSQL not found"
fi

# Check Redis
if command -v redis-server &> /dev/null; then
    echo "✅ Redis found"
else
    echo "❌ Redis not found"
fi
```

### Environment Setup Script

```bash
#!/bin/bash
# setup-local-env.sh

echo "Setting up local development environment..."

# Backend setup
cd be

# Install dependencies
composer install

# Environment setup
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env file"
fi

# Generate key
php artisan key:generate

# Database setup
php artisan migrate --force

# Storage link
php artisan storage:link

# Clear caches
php artisan config:clear
php artisan cache:clear

cd ..

# Frontend setup
cd fe
npm install
cd ..

echo "Local environment setup complete!"
```

## 🚀 Production Configuration

### Security Settings

```env
# Production environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Strong database credentials
DB_PASSWORD=your_secure_password_here

# Redis with password
REDIS_PASSWORD=your_redis_password_here

# Secure session settings
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

### Performance Optimization

```env
# Cache configuration
CACHE_STORE=redis
CACHE_PREFIX=bulkbridge

# Queue configuration
QUEUE_CONNECTION=redis
QUEUE_RETRY_AFTER=90

# Session configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

### Laravel Optimization Commands

```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize Composer
composer install --optimize-autoloader --no-dev
```

## 🔄 Environment Switching

### Development to Production

1. **Update Environment Variables**:
   ```bash
   # Copy production environment
   cp .env.production .env
   
   # Update database credentials
   # Update Redis credentials
   # Update APP_URL
   ```

2. **Run Optimizations**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Build Frontend**:
   ```bash
   cd fe
   npm run build
   ```

### Docker to Local

1. **Stop Docker Services**:
   ```bash
   docker compose down
   ```

2. **Start Local Services**:
   ```bash
   ./run-local.sh start
   ```

3. **Update Configuration**:
   - Change API URLs in frontend
   - Update database connections
   - Configure Redis connections

## 🛠️ Configuration Validation

### Backend Validation

```bash
# Check configuration
php artisan config:show

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()

# Test Redis connection
>>> Redis::ping()

# Check queue configuration
>>> config('queue.default')
```

### Frontend Validation

```bash
# Check if API is accessible
curl http://localhost:8000/api/health

# Check React build
cd fe && npm run build
```

## 📊 Monitoring Configuration

### Log Configuration

```env
# Logging configuration
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
LOG_DEPRECATIONS_CHANNEL=null
```

### Health Check Endpoints

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'redis' => Redis::ping() ? 'connected' : 'disconnected',
    ]);
});
```

## 🔐 Security Configuration

### CORS Configuration

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'], // Add production domain
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

### File Upload Security

```php
// config/filesystems.php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

## 📝 Configuration Checklist

### Before Starting Development

- [ ] PostgreSQL database created
- [ ] Redis server running
- [ ] PHP extensions installed
- [ ] Composer dependencies installed
- [ ] Node.js dependencies installed
- [ ] Environment file configured
- [ ] Database migrations run
- [ ] Storage link created

### Before Production Deployment

- [ ] Environment variables updated
- [ ] Database credentials secured
- [ ] Redis password set
- [ ] APP_DEBUG=false
- [ ] APP_URL set to production domain
- [ ] SSL certificates configured
- [ ] CORS origins updated
- [ ] File upload limits configured
- [ ] Logging configured
- [ ] Health checks implemented

---

This configuration guide covers the essential settings for running BulkBridge in different environments. Adjust the values according to your specific requirements and infrastructure setup.
