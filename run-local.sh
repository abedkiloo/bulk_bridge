#!/bin/bash

# BulkBridge - Local Development Setup (Without Docker)
# This script sets up and runs the BulkBridge application locally

set -e

echo "🚀 BulkBridge Local Development Setup"
echo "======================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if required software is installed
check_requirements() {
    print_status "Checking system requirements..."
    
    # Check PHP
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed. Please install PHP 8.2+ and try again."
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_success "PHP $PHP_VERSION found"
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        print_error "Composer is not installed. Please install Composer and try again."
        exit 1
    fi
    
    print_success "Composer found"
    
    # Check Node.js
    if ! command -v node &> /dev/null; then
        print_error "Node.js is not installed. Please install Node.js 18+ and try again."
        exit 1
    fi
    
    NODE_VERSION=$(node --version)
    print_success "Node.js $NODE_VERSION found"
    
    # Check npm
    if ! command -v npm &> /dev/null; then
        print_error "npm is not installed. Please install npm and try again."
        exit 1
    fi
    
    print_success "npm found"
    
    # Check PostgreSQL
    if ! command -v psql &> /dev/null; then
        print_error "PostgreSQL is not installed. Please install PostgreSQL and try again."
        exit 1
    fi
    
    print_success "PostgreSQL found"
    
    # Check Redis
    if ! command -v redis-server &> /dev/null; then
        print_warning "Redis is not installed. Please install Redis for queue processing."
        print_warning "You can continue without Redis, but queue processing will not work."
        read -p "Continue without Redis? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    else
        print_success "Redis found"
    fi
}

# Setup backend
setup_backend() {
    print_status "Setting up backend..."
    
    cd be
    
    # Install PHP dependencies
    print_status "Installing PHP dependencies..."
    composer install
    
    # Copy environment file
    if [ ! -f .env ]; then
        print_status "Creating .env file..."
        cp .env.example .env
    fi
    
    # Generate application key
    print_status "Generating application key..."
    php artisan key:generate
    
    # Clear caches
    print_status "Clearing caches..."
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    
    # Run migrations
    print_status "Running database migrations..."
    php artisan migrate --force
    
    # Create storage link
    print_status "Creating storage link..."
    php artisan storage:link
    
    cd ..
    print_success "Backend setup complete!"
}

# Setup frontend
setup_frontend() {
    print_status "Setting up frontend..."
    
    cd fe
    
    # Install Node.js dependencies
    print_status "Installing Node.js dependencies..."
    npm install
    
    cd ..
    print_success "Frontend setup complete!"
}

# Start Redis
start_redis() {
    if command -v redis-server &> /dev/null; then
        print_status "Starting Redis..."
        
        # Check if Redis is already running
        if pgrep -x "redis-server" > /dev/null; then
            print_success "Redis is already running"
        else
            # Start Redis in background
            redis-server --daemonize yes
            print_success "Redis started"
        fi
    else
        print_warning "Redis not available - queue processing will not work"
    fi
}

# Start queue worker
start_queue_worker() {
    if command -v redis-server &> /dev/null; then
        print_status "Starting queue worker..."
        cd be
        php artisan queue:work --daemon &
        QUEUE_PID=$!
        echo $QUEUE_PID > ../queue_worker.pid
        cd ..
        print_success "Queue worker started (PID: $QUEUE_PID)"
    else
        print_warning "Skipping queue worker - Redis not available"
    fi
}

# Start Laravel development server
start_laravel_server() {
    print_status "Starting Laravel development server..."
    cd be
    php artisan serve --host=0.0.0.0 --port=8000 &
    LARAVEL_PID=$!
    echo $LARAVEL_PID > ../laravel_server.pid
    cd ..
    print_success "Laravel server started (PID: $LARAVEL_PID)"
}

# Start React development server
start_react_server() {
    print_status "Starting React development server..."
    cd fe
    npm start &
    REACT_PID=$!
    echo $REACT_PID > ../react_server.pid
    cd ..
    print_success "React server started (PID: $REACT_PID)"
}

# Stop all services
stop_services() {
    print_status "Stopping all services..."
    
    # Stop Laravel server
    if [ -f laravel_server.pid ]; then
        LARAVEL_PID=$(cat laravel_server.pid)
        if kill -0 $LARAVEL_PID 2>/dev/null; then
            kill $LARAVEL_PID
            print_success "Laravel server stopped"
        fi
        rm -f laravel_server.pid
    fi
    
    # Stop React server
    if [ -f react_server.pid ]; then
        REACT_PID=$(cat react_server.pid)
        if kill -0 $REACT_PID 2>/dev/null; then
            kill $REACT_PID
            print_success "React server stopped"
        fi
        rm -f react_server.pid
    fi
    
    # Stop queue worker
    if [ -f queue_worker.pid ]; then
        QUEUE_PID=$(cat queue_worker.pid)
        if kill -0 $QUEUE_PID 2>/dev/null; then
            kill $QUEUE_PID
            print_success "Queue worker stopped"
        fi
        rm -f queue_worker.pid
    fi
    
    # Stop Redis if we started it
    if [ "$REDIS_STARTED" = "true" ]; then
        print_status "Stopping Redis..."
        redis-cli shutdown
        print_success "Redis stopped"
    fi
}

# Cleanup function
cleanup() {
    print_status "Cleaning up..."
    stop_services
    exit 0
}

# Set up signal handlers
trap cleanup SIGINT SIGTERM

# Main execution
main() {
    case "${1:-start}" in
        "start")
            check_requirements
            setup_backend
            setup_frontend
            start_redis
            start_queue_worker
            start_laravel_server
            start_react_server
            
            print_success "🎉 BulkBridge is now running locally!"
            echo ""
            echo "🌐 Application URLs:"
            echo "   Frontend: http://localhost:3000"
            echo "   Backend API: http://localhost:8000/api"
            echo "   Health Check: http://localhost:8000/health"
            echo ""
            echo "📊 Service Status:"
            echo "   Laravel Server: http://localhost:8000"
            echo "   React Server: http://localhost:3000"
            echo "   Queue Worker: Running in background"
            echo "   Redis: Running in background"
            echo ""
            echo "📝 Useful Commands:"
            echo "   Stop services: ./run-local.sh stop"
            echo "   Restart services: ./run-local.sh restart"
            echo "   View logs: tail -f be/storage/logs/laravel.log"
            echo "   Access backend shell: cd be && php artisan tinker"
            echo ""
            echo "Press Ctrl+C to stop all services"
            
            # Wait for user to stop
            while true; do
                sleep 1
            done
            ;;
        "stop")
            stop_services
            print_success "All services stopped"
            ;;
        "restart")
            stop_services
            sleep 2
            main start
            ;;
        "setup")
            check_requirements
            setup_backend
            setup_frontend
            print_success "Setup complete! Run './run-local.sh start' to start the application"
            ;;
        "backend")
            check_requirements
            setup_backend
            start_redis
            start_queue_worker
            start_laravel_server
            print_success "Backend services started!"
            echo "Backend API: http://localhost:8000/api"
            echo "Press Ctrl+C to stop"
            while true; do
                sleep 1
            done
            ;;
        "frontend")
            check_requirements
            setup_frontend
            start_react_server
            print_success "Frontend service started!"
            echo "Frontend: http://localhost:3000"
            echo "Press Ctrl+C to stop"
            while true; do
                sleep 1
            done
            ;;
        *)
            echo "Usage: $0 {start|stop|restart|setup|backend|frontend}"
            echo ""
            echo "Commands:"
            echo "  start     - Start all services (default)"
            echo "  stop      - Stop all services"
            echo "  restart   - Restart all services"
            echo "  setup     - Setup the application without starting"
            echo "  backend   - Start only backend services"
            echo "  frontend  - Start only frontend service"
            exit 1
            ;;
    esac
}

main "$@"
