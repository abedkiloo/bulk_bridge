#!/bin/bash

# BulkBridge Setup Script
# This script sets up the entire BulkBridge application

set -e  # Exit on any error

echo "🚀 BulkBridge Setup Script"
echo "=========================="
echo ""

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

# Check prerequisites
print_status "Checking prerequisites..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker &> /dev/null || ! docker compose version &> /dev/null; then
    print_error "Docker Compose is not installed or not working. Please install Docker Compose first."
    exit 1
fi

# Check if PostgreSQL is running
if ! pg_isready -q; then
    print_warning "PostgreSQL is not running. Please start PostgreSQL first."
    print_status "On macOS: brew services start postgresql"
    print_status "On Ubuntu: sudo systemctl start postgresql"
    exit 1
fi

print_success "All prerequisites are met!"

# Check if database exists
print_status "Checking database setup..."

# Try to connect to PostgreSQL and create database if it doesn't exist
if psql -U abedkiloo -d bulkbridge -c "SELECT 1;" &> /dev/null; then
    print_success "Database 'bulkbridge' already exists"
else
    print_status "Creating database 'bulkbridge'..."
    if createdb -U abedkiloo bulkbridge; then
        print_success "Database 'bulkbridge' created successfully"
    else
        print_error "Failed to create database. Please check your PostgreSQL setup."
        print_status "Make sure user 'abedkiloo' exists and has CREATE DATABASE privileges"
        exit 1
    fi
fi

# Make scripts executable
print_status "Setting up executable permissions..."
chmod +x docker.sh
chmod +x be/start-queue.sh
print_success "Scripts are now executable"

# Start the application
print_status "Starting BulkBridge application..."
print_status "This may take a few minutes for the first run..."

if ./docker.sh; then
    print_success "BulkBridge is now running!"
    echo ""
    echo "🌐 Access your application:"
    echo "   Frontend: http://localhost:3000"
    echo "   Backend API: http://localhost:8000/api"
    echo "   Database: localhost:5432"
    echo "   Redis: localhost:6380"
    echo ""
    echo "📚 Next steps:"
    echo "   1. Open http://localhost:3000 in your browser"
    echo "   2. Upload a CSV file with employee data"
    echo "   3. Monitor the import progress in real-time"
    echo ""
    echo "🛠️ Useful commands:"
    echo "   View logs: docker compose logs -f [service]"
    echo "   Stop services: docker compose down"
    echo "   Restart: ./docker.sh"
    echo ""
    print_success "Setup complete! Enjoy using BulkBridge! 🎉"
else
    print_error "Failed to start the application. Check the logs above for details."
    exit 1
fi
