#!/bin/bash

# BulkBridge Docker Setup Script
echo "🚀 Setting up BulkBridge with Docker..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

print_status "Docker and Docker Compose are installed ✓"

# Create necessary directories
print_status "Creating necessary directories..."
mkdir -p docker/nginx/ssl
mkdir -p be/docker/supervisor

# Copy environment file for Docker
if [ ! -f "be/.env" ]; then
    print_status "Creating .env file for backend..."
    cp docker.env be/.env
    print_warning "Please update be/.env with your actual configuration values"
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" be/.env || grep -q "APP_KEY=base64:your-app-key-here" be/.env; then
    print_status "Generating application key..."
    cd be
    php artisan key:generate --no-interaction
    cd ..
fi

# Build and start services
print_status "Building Docker images..."
docker compose build

print_status "Starting services..."
docker compose up -d

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 10

# Run database migrations
print_status "Running database migrations..."
docker compose exec backend php artisan migrate --force

# Create storage link
print_status "Creating storage link..."
docker compose exec backend php artisan storage:link

# Clear caches
print_status "Clearing caches..."
docker compose exec backend php artisan config:clear
docker compose exec backend php artisan cache:clear
docker compose exec backend php artisan route:clear

print_status "✅ BulkBridge is now running!"
echo ""
echo "🌐 Application URLs:"
echo "   Frontend: http://localhost"
echo "   Backend API: http://localhost/api"
echo "   Health Check: http://localhost/health"
echo ""
echo "📊 Service Status:"
docker compose ps
echo ""
echo "📝 Useful Commands:"
echo "   View logs: docker compose logs -f"
echo "   Stop services: docker compose down"
echo "   Restart services: docker compose restart"
echo "   Access backend shell: docker compose exec backend bash"
echo "   Access database: docker compose exec postgres psql -U bulkbridge -d bulkbridge"
echo ""
print_status "Setup complete! 🎉"
