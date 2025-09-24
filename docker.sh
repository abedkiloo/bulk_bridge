#!/bin/bash

# BulkBridge Docker Development Script
echo "🚀 Starting BulkBridge Development Environment..."

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Generate app key if not exists
if [ ! -f "be/.env" ]; then
    echo "📝 Creating .env file for backend..."
    cp be/.env.example be/.env
    docker run --rm -v "${SCRIPT_DIR}/be:/app" php:8.2-cli php -r "echo 'APP_KEY=base64:' . base64_encode(random_bytes(32)) . PHP_EOL;" >> be/.env
fi

# Update .env to use local database
echo "🗄️ Configuring to use local PostgreSQL database..."
sed -i.bak 's/DB_HOST=.*/DB_HOST=host.docker.internal/' be/.env
sed -i.bak 's/DB_DATABASE=.*/DB_DATABASE=bulkbridge/' be/.env
sed -i.bak 's/DB_USERNAME=.*/DB_USERNAME=abedkiloo/' be/.env
sed -i.bak 's/DB_PASSWORD=.*/DB_PASSWORD=/' be/.env

# Build and start development services
echo "🔨 Building and starting development services..."
docker compose up --build -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 10

# Run database migrations
echo "🗄️ Running database migrations..."
docker compose exec backend php artisan migrate --force

# Install frontend dependencies
echo "📦 Installing frontend dependencies..."
docker compose exec frontend npm install

# Clear caches
echo "🧹 Clearing caches..."
docker compose exec backend php artisan config:clear
docker compose exec backend php artisan cache:clear
docker compose exec backend php artisan route:clear

# Show status
echo "📊 Development Service Status:"
docker compose ps

echo ""
echo "✅ BulkBridge Development Environment is now running!"
echo "🌐 Frontend: http://localhost:3000 (with hot reload)"
echo "🔧 Backend API: http://localhost:8000/api"
echo "🗄️ PostgreSQL: localhost:5432 (your local database)"
echo "🔴 Redis: localhost:6380"
echo ""
echo "📋 Useful commands:"
echo "  docker compose logs -f [service]  # View logs"
echo "  docker compose exec backend php artisan [command]  # Run artisan commands"
echo "  docker compose down  # Stop all services"
