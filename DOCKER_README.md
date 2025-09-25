# BulkBridge Docker Setup

This document provides instructions for running BulkBridge using Docker and Docker Compose.

## Prerequisites

- Docker (version 20.10 or higher)
- Docker Compose (version 2.0 or higher)

## Quick Start

1. **Clone the repository and navigate to the project directory**
   ```bash
   git clone <repository-url>
   cd BulkBridge
   ```

2. **Run the setup script**
   ```bash
   ./docker-setup.sh
   ```

3. **Access the application**
   - Frontend: http://localhost
   - Backend API: http://localhost/api
   - Health Check: http://localhost/health

## Manual Setup

If you prefer to set up manually:

1. **Create environment file**
   ```bash
   cp docker.env be/.env
   ```

2. **Generate application key**
   ```bash
   cd be
   php artisan key:generate
   cd ..
   ```

3. **Build and start services**
   ```bash
   docker compose build
   docker compose up -d
   ```

4. **Run database migrations**
   ```bash
   docker compose exec backend php artisan migrate --force
   ```

5. **Create storage link**
   ```bash
   docker compose exec backend php artisan storage:link
   ```

## Service Architecture

The Docker setup includes the following services:

- **PostgreSQL**: Database server
- **Redis**: Cache and queue server
- **Backend**: Laravel application with PHP-FPM
- **Frontend**: React application served by Nginx
- **Nginx**: Reverse proxy and load balancer

## Management Commands

Use the `docker-manage.sh` script for common operations:

```bash
# Start services
./docker-manage.sh start

# Stop services
./docker-manage.sh stop

# View logs
./docker-manage.sh logs

# Access backend shell
./docker-manage.sh shell-be

# Run migrations
./docker-manage.sh migrate

# Clear caches
./docker-manage.sh clear

# View service status
./docker-manage.sh status
```

## Environment Configuration

The application uses the following environment variables:

### Database
- `DB_HOST=postgres`
- `DB_PORT=5432`
- `DB_DATABASE=bulkbridge`
- `DB_USERNAME=bulkbridge`
- `DB_PASSWORD=bulkbridge_password`

### Redis
- `REDIS_HOST=redis`
- `REDIS_PORT=6379`
- `REDIS_PASSWORD=null`

### Application
- `APP_ENV=production`
- `APP_DEBUG=false`
- `QUEUE_CONNECTION=redis-imports`
- `CACHE_DRIVER=redis`
- `SESSION_DRIVER=redis`

## File Uploads

File uploads are handled with the following configuration:
- Maximum file size: 50MB (configured in Nginx)
- Upload rate limiting: 2 requests per second
- Files are stored in the `backend_storage` Docker volume

## Monitoring and Logs

### View Logs
```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f postgres
docker compose logs -f redis
```

### Health Checks
- Application health: http://localhost/health
- Database health: Built-in PostgreSQL health check
- Redis health: Built-in Redis health check

## Development

### Accessing Services

**Backend Shell**
```bash
docker compose exec backend bash
```

**Database Shell**
```bash
docker compose exec postgres psql -U bulkbridge -d bulkbridge
```

**Redis CLI**
```bash
docker compose exec redis redis-cli
```

### Running Commands

**Laravel Commands**
```bash
docker compose exec backend php artisan [command]
```

**Composer Commands**
```bash
docker compose exec backend composer [command]
```

**NPM Commands (Frontend)**
```bash
docker compose exec frontend npm [command]
```

## Troubleshooting

### Common Issues

1. **Services not starting**
   ```bash
   docker compose logs [service-name]
   ```

2. **Database connection issues**
   - Check if PostgreSQL is healthy: `docker compose ps`
   - Verify environment variables in `docker.env`

3. **File permission issues**
   ```bash
   docker compose exec backend chown -R www-data:www-data /var/www/html/storage
   ```

4. **Port conflicts**
   - Check if ports 80, 3000, 5432, 6379 are available
   - Modify ports in `docker-compose.yml` if needed

### Cleanup

**Remove all containers and volumes**
```bash
./docker-manage.sh clean
```

**Remove only containers**
```bash
docker compose down
```

**Remove containers and volumes**
```bash
docker compose down -v
```

## Production Considerations

For production deployment:

1. **Update environment variables**
   - Set strong passwords
   - Configure proper APP_KEY
   - Set APP_DEBUG=false

2. **SSL/TLS Configuration**
   - Add SSL certificates to `docker/nginx/ssl/`
   - Update Nginx configuration for HTTPS

3. **Security**
   - Use secrets management
   - Configure firewall rules
   - Enable security headers

4. **Monitoring**
   - Set up log aggregation
   - Configure health checks
   - Monitor resource usage

## Support

For issues and questions:
- Check the logs: `./docker-manage.sh logs`
- Verify service status: `./docker-manage.sh status`
- Review the troubleshooting section above
