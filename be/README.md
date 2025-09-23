# Workpay Bulk Employee Import System

An enterprise-grade bulk import system built with Laravel that handles CSV file uploads, validates employee data, and imports thousands of records efficiently with real-time progress tracking.

## 🚀 Features

- **Large CSV Processing**: Handles files with thousands of rows efficiently
- **Real-time Progress Tracking**: Live updates on import progress via API
- **Comprehensive Validation**: Row-by-row validation with detailed error reporting
- **Idempotent Operations**: Safe to retry without creating duplicates
- **Duplicate Detection**: Handles duplicates within files and existing database records
- **Error Handling**: Detailed error tracking and reporting
- **Async Processing**: Queue-based processing for scalability

## 🏗️ Architecture

### Database Schema
- **employees**: Core employee data with proper indexing
- **import_jobs**: Import job tracking and metadata
- **import_rows**: Individual row processing status
- **import_errors**: Detailed error tracking and categorization

### Key Components
- **Models**: Eloquent models with relationships and business logic
- **Jobs**: Queue-based processing for scalability
- **Services**: Business logic separation (CSV parsing, validation)
- **Controllers**: API endpoints with comprehensive error handling

## 📋 Requirements

- PHP 8.1+
- Laravel 12.x
- PostgreSQL 12+ (recommended) or MySQL 8.0+
- Redis (for caching and queues)
- Composer

## 🛠️ Installation

1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database setup (PostgreSQL)**
   
   **Option A: Using the setup script (recommended)**
   ```bash
   chmod +x setup-postgresql.sh
   ./setup-postgresql.sh
   ```
   
   **Option B: Manual setup**
   ```bash
   # Install PostgreSQL (macOS)
   brew install postgresql
   brew services start postgresql
   
   # Create database and user
   sudo -u postgres psql
   CREATE USER postgres WITH PASSWORD 'password';
   CREATE DATABASE bulk_import OWNER postgres;
   GRANT ALL PRIVILEGES ON DATABASE bulk_import TO postgres;
   \q
   
   # Run migrations
   php artisan migrate
   ```

4. **Start the queue worker**
   ```bash
   php artisan queue:work --queue=imports
   ```

5. **Start the application**
   ```bash
   php artisan serve
   ```

## 🚀 Usage

### Upload CSV File
```bash
POST /api/imports
Content-Type: multipart/form-data

# Form data:
csv_file: [CSV file]
```

### Get Import Progress
```bash
GET /api/imports/{job_id}
```

### List Import Jobs
```bash
GET /api/imports?status=completed&per_page=10
```

### CSV Format

```csv
employee_number,first_name,last_name,email,department,salary,currency,country_code,start_date
EMP-12345678,John,Doe,john.doe@workmail.co,Engineering,75000,USD,US,2020-01-15
EMP-87654321,Jane,Smith,jane.smith@company.africa,Finance,65000,EUR,GB,2019-06-01
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter BulkImportTest
```

## 📊 Sample Data

Sample CSV files are provided in `storage/app/sample_data/`:

- `sample_employees_valid.csv`: Clean data for testing success scenarios
- `sample_employees_with_errors.csv`: Data with various validation errors

## 🔧 Configuration

### Database Configuration (PostgreSQL)
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bulk_import
DB_USERNAME=postgres
DB_PASSWORD=password
```

### Queue Configuration
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### File Upload Limits
- Maximum file size: 20MB
- Maximum rows: 50,000
- Batch size: 100 rows per job

## 📈 Performance

- **Database Indexing**: Optimized indexes for fast lookups
- **Batch Processing**: Memory-efficient processing of large files
- **Caching**: Redis caching for progress updates
- **Queue Processing**: Async processing prevents timeouts

## 🚨 Error Handling

### Error Types
- **Validation Errors**: Field-level validation failures
- **Duplicate Errors**: Duplicate employee numbers or emails
- **System Errors**: Database or processing failures
- **Business Logic Errors**: Custom business rule violations

## 🔒 Security

- **File Type Validation**: Only CSV files allowed
- **Size Limits**: Maximum file size enforcement
- **Input Sanitization**: All input data sanitized
- **SQL Injection Prevention**: Eloquent ORM protection

## 📝 API Endpoints

- `POST /api/imports` - Upload CSV file
- `GET /api/imports` - List import jobs
- `GET /api/imports/{job_id}` - Get import progress
- `GET /api/imports/{job_id}/errors` - Get import errors
- `GET /api/imports/{job_id}/rows` - Get import rows
- `POST /api/imports/{job_id}/cancel` - Cancel import job
- `GET /api/employees` - List employees
- `GET /api/employees/{id}` - Get specific employee
- `DELETE /api/employees/{id}` - Delete employee
- `DELETE /api/employees` - Clear all data (demo)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## 📄 License

This project is licensed under the MIT License.