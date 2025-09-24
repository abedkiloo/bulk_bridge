# Troubleshooting Guide

## 🚨 Common Issues and Solutions

### CSV File Validation Failed

**Error Message:**
```json
{
    "success": false,
    "message": "CSV file validation failed"
}
```

#### Possible Causes and Solutions:

##### 1. **File Format Issues**

**Problem:** CSV file doesn't match expected format
**Solution:** Ensure your CSV has exactly these 9 columns in this order:
```
employee_number,first_name,last_name,email,department,salary,currency,country_code,start_date
```

**Example valid row:**
```
EMP-12345678,John,Doe,john.doe@example.com,Engineering,50000,USD,US,2023-01-01
```

##### 2. **File Extension Issues**

**Problem:** File extension not recognized
**Solution:** 
- Use `.csv` or `.txt` extension
- Ensure file is actually CSV format, not Excel (.xlsx) or other format

##### 3. **Header Issues**

**Problem:** Missing or incorrect headers
**Solution:**
- Check that headers match exactly (case-sensitive)
- No extra spaces or special characters
- Headers must be in the first row

##### 4. **File Size Issues**

**Problem:** File too large
**Solution:**
- Maximum file size: 20MB
- Maximum rows: 50,000
- Split large files into smaller chunks

##### 5. **File Encoding Issues**

**Problem:** Special characters not displaying correctly
**Solution:**
- Save file as UTF-8 encoding
- Avoid special characters in data
- Use standard ASCII characters when possible

#### Debugging Steps:

1. **Test with sample file:**
   ```bash
   php test-csv-validation.php
   ```

2. **Check file format:**
   ```bash
   php debug-csv-validation.php your_file.csv
   ```

3. **Test API upload:**
   ```bash
   php test-api-upload.php
   ```

4. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Database Connection Issues

**Error:** Database connection failed

#### Solutions:

1. **Check PostgreSQL status:**
   ```bash
   pg_isready
   ```

2. **Test database connection:**
   ```bash
   php test-db-connection.php
   ```

3. **Run setup script:**
   ```bash
   ./setup-postgresql.sh
   ```

4. **Check environment variables:**
   ```bash
   cat .env | grep DB_
   ```

### Queue Processing Issues

**Error:** Jobs not processing

#### Solutions:

1. **Start queue worker:**
   ```bash
   php artisan queue:work --queue=imports
   ```

2. **Check queue status:**
   ```bash
   php artisan queue:monitor
   ```

3. **Clear failed jobs:**
   ```bash
   php artisan queue:flush
   ```

4. **Check Redis connection:**
   ```bash
   redis-cli ping
   ```

### File Upload Issues

**Error:** File upload failed

#### Solutions:

1. **Check file permissions:**
   ```bash
   ls -la storage/app/
   chmod -R 775 storage/
   ```

2. **Check disk space:**
   ```bash
   df -h
   ```

3. **Verify upload limits:**
   - PHP: `upload_max_filesize` and `post_max_size`
   - Laravel: Check `StoreImportRequest` validation rules

### API Endpoint Issues

**Error:** 404 Not Found

#### Solutions:

1. **Check routes:**
   ```bash
   php artisan route:list
   ```

2. **Clear route cache:**
   ```bash
   php artisan route:clear
   ```

3. **Check server is running:**
   ```bash
   php artisan serve
   ```

## 🔧 Diagnostic Tools

### 1. CSV Validation Test
```bash
php test-csv-validation.php
```

### 2. Database Connection Test
```bash
php test-db-connection.php
```

### 3. API Upload Test
```bash
php test-api-upload.php
```

### 4. CSV Debug Tool
```bash
php debug-csv-validation.php your_file.csv
```

### 5. Laravel Tinker
```bash
php artisan tinker
```

## 📋 Common CSV Format Issues

### ❌ Common Mistakes:

1. **Wrong column order:**
   ```
   first_name,employee_number,last_name,email,department,salary,currency,country_code,start_date
   ```

2. **Missing columns:**
   ```
   employee_number,first_name,last_name,email,department,salary,currency,country_code
   ```

3. **Extra columns:**
   ```
   employee_number,first_name,last_name,email,department,salary,currency,country_code,start_date,extra_column
   ```

4. **Wrong header names:**
   ```
   emp_number,first_name,last_name,email,department,salary,currency,country_code,start_date
   ```

5. **Case sensitivity:**
   ```
   Employee_Number,First_Name,Last_Name,Email,Department,Salary,Currency,Country_Code,Start_Date
   ```

### ✅ Correct Format:

```
employee_number,first_name,last_name,email,department,salary,currency,country_code,start_date
EMP-12345678,John,Doe,john.doe@example.com,Engineering,50000,USD,US,2023-01-01
EMP-87654321,Jane,Smith,jane.smith@example.com,Marketing,45000,USD,US,2023-02-01
```

## 🚀 Quick Fixes

### Reset Everything:
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restart queue worker
php artisan queue:restart

# Check logs
tail -f storage/logs/laravel.log
```

### Test with Sample Data:
```bash
# Use provided sample files
curl -X POST http://localhost:8000/api/imports \
  -F "csv_file=@storage/app/sample_data/sample_employees_valid.csv"
```

### Check System Status:
```bash
# Database
php artisan migrate:status

# Queue
php artisan queue:monitor

# Storage
ls -la storage/app/imports/
```

## 📞 Getting Help

If you're still experiencing issues:

1. **Check the logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Run diagnostics:**
   ```bash
   php test-csv-validation.php
   php test-db-connection.php
   ```

3. **Verify environment:**
   ```bash
   php artisan env
   ```

4. **Check system requirements:**
   - PHP 8.1+
   - PostgreSQL 12+
   - Redis
   - Composer dependencies

## 🎯 Success Indicators

Your system is working correctly when:

- ✅ CSV validation passes
- ✅ Database connection successful
- ✅ Queue worker processing jobs
- ✅ API endpoints responding
- ✅ Files uploading successfully
- ✅ Import jobs completing

## 📝 Log Locations

- **Laravel logs:** `storage/logs/laravel.log`
- **Queue logs:** Check queue worker output
- **Database logs:** PostgreSQL logs (system dependent)
- **Web server logs:** Apache/Nginx logs (system dependent)
