#!/bin/bash

# BulkBridge Docker Management Script

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

print_header() {
    echo -e "${BLUE}[BULKBRIDGE]${NC} $1"
}

# Function to show help
show_help() {
    echo "BulkBridge Docker Management Script"
    echo ""
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  start       Start all services"
    echo "  stop        Stop all services"
    echo "  restart     Restart all services"
    echo "  build       Build all Docker images"
    echo "  logs        Show logs for all services"
    echo "  logs-be     Show backend logs only"
    echo "  logs-fe     Show frontend logs only"
    echo "  logs-db     Show database logs only"
    echo "  status      Show service status"
    echo "  shell-be    Access backend container shell"
    echo "  shell-db    Access database shell"
    echo "  migrate     Run database migrations"
    echo "  seed        Run database seeders"
    echo "  clear       Clear Laravel caches"
    echo "  queue       Start queue worker"
    echo "  test        Run tests"
    echo "  clean       Clean up containers and volumes"
    echo "  help        Show this help message"
    echo ""
}

# Function to check if services are running
check_services() {
    if ! docker compose ps | grep -q "Up"; then
        print_warning "Services are not running. Use '$0 start' to start them."
        return 1
    fi
    return 0
}

# Main script logic
case "${1:-help}" in
    start)
        print_header "Starting BulkBridge services..."
        docker compose up -d
        print_status "Services started successfully!"
        ;;
    
    stop)
        print_header "Stopping BulkBridge services..."
        docker compose down
        print_status "Services stopped successfully!"
        ;;
    
    restart)
        print_header "Restarting BulkBridge services..."
        docker compose restart
        print_status "Services restarted successfully!"
        ;;
    
    build)
        print_header "Building Docker images..."
        docker compose build --no-cache
        print_status "Images built successfully!"
        ;;
    
    logs)
        print_header "Showing logs for all services..."
        docker compose logs -f
        ;;
    
    logs-be)
        print_header "Showing backend logs..."
        docker compose logs -f backend
        ;;
    
    logs-fe)
        print_header "Showing frontend logs..."
        docker compose logs -f frontend
        ;;
    
    logs-db)
        print_header "Showing database logs..."
        docker compose logs -f postgres
        ;;
    
    status)
        print_header "Service Status:"
        docker compose ps
        echo ""
        print_header "Resource Usage:"
        docker stats --no-stream
        ;;
    
    shell-be)
        if check_services; then
            print_header "Accessing backend shell..."
            docker compose exec backend bash
        fi
        ;;
    
    shell-db)
        if check_services; then
            print_header "Accessing database shell..."
            docker compose exec postgres psql -U bulkbridge -d bulkbridge
        fi
        ;;
    
    migrate)
        if check_services; then
            print_header "Running database migrations..."
            docker compose exec backend php artisan migrate --force
            print_status "Migrations completed!"
        fi
        ;;
    
    seed)
        if check_services; then
            print_header "Running database seeders..."
            docker compose exec backend php artisan db:seed --force
            print_status "Seeders completed!"
        fi
        ;;
    
    clear)
        if check_services; then
            print_header "Clearing Laravel caches..."
            docker compose exec backend php artisan config:clear
            docker compose exec backend php artisan cache:clear
            docker compose exec backend php artisan route:clear
            docker compose exec backend php artisan view:clear
            print_status "Caches cleared!"
        fi
        ;;
    
    queue)
        if check_services; then
            print_header "Starting queue worker..."
            docker compose exec backend php artisan queue:work redis-imports --queue=imports-high-priority --timeout=3600 --tries=3 --verbose
        fi
        ;;
    
    test)
        if check_services; then
            print_header "Running tests..."
            docker compose exec backend php artisan test
        fi
        ;;
    
    clean)
        print_header "Cleaning up Docker resources..."
        print_warning "This will remove all containers, volumes, and images. Are you sure? (y/N)"
        read -r response
        if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
            docker compose down -v --rmi all
            docker system prune -f
            print_status "Cleanup completed!"
        else
            print_status "Cleanup cancelled."
        fi
        ;;
    
    help|*)
        show_help
        ;;
esac
