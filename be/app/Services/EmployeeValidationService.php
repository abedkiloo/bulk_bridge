<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmployeeValidationService
{
    /**
     * Validate a single row of employee data
     */
    public function validateRow(array $rowData): array
    {
        try {
            $validator = Validator::make($rowData, Employee::getValidationRules());
            
            if ($validator->fails()) {
                return [
                    'valid' => false,
                    'errors' => $validator->errors()->toArray()
                ];
            }
            
            // Additional business logic validations
            $businessValidation = $this->validateBusinessRules($rowData);
            if (!$businessValidation['valid']) {
                return $businessValidation;
            }
            
            return [
                'valid' => true,
                'errors' => []
            ];
            
        } catch (ValidationException $e) {
            return [
                'valid' => false,
                'errors' => $e->errors()
            ];
        }
    }

    /**
     * Validate business rules
     */
    private function validateBusinessRules(array $rowData): array
    {
        $errors = [];
        
        // Validate salary range
        if (isset($rowData['salary'])) {
            $salary = (float) $rowData['salary'];
            if ($salary < 0) {
                $errors['salary'][] = 'Salary cannot be negative';
            }
            if ($salary > 10000000) { // 10 million
                $errors['salary'][] = 'Salary seems unreasonably high';
            }
        }
        
        // Validate currency codes
        if (isset($rowData['currency'])) {
            $validCurrencies = ['USD', 'EUR', 'GBP', 'ZAR', 'KES', 'UGX', 'TZS', 'RWF', 'NGN'];
            if (!in_array(strtoupper($rowData['currency']), $validCurrencies)) {
                $errors['currency'][] = 'Invalid currency code. Must be one of: ' . implode(', ', $validCurrencies);
            }
        }
        
        // Validate country codes
        if (isset($rowData['country_code'])) {
            $validCountries = ['US', 'GB', 'ZA', 'KE', 'UG', 'TZ', 'RW', 'NG'];
            if (!in_array(strtoupper($rowData['country_code']), $validCountries)) {
                $errors['country_code'][] = 'Invalid country code. Must be one of: ' . implode(', ', $validCountries);
            }
        }
        
        // Validate start date
        if (isset($rowData['start_date'])) {
            $startDate = \Carbon\Carbon::parse($rowData['start_date']);
            if ($startDate->isFuture()) {
                $errors['start_date'][] = 'Start date cannot be in the future';
            }
            if ($startDate->isBefore('1900-01-01')) {
                $errors['start_date'][] = 'Start date cannot be before 1900';
            }
        }
        
        // Validate email domain
        if (isset($rowData['email'])) {
            $email = $rowData['email'];
            $allowedDomains = ['workmail.co', 'company.africa', 'mail.test'];
            $emailDomain = substr(strrchr($email, "@"), 1);
            
            if (!in_array($emailDomain, $allowedDomains)) {
                $errors['email'][] = 'Email domain not allowed. Must be one of: ' . implode(', ', $allowedDomains);
            }
        }
        
        // Validate employee number format
        if (isset($rowData['employee_number'])) {
            $empNumber = $rowData['employee_number'];
            if (!preg_match('/^EMP-\d{8}$/', $empNumber)) {
                $errors['employee_number'][] = 'Employee number must be in format EMP-XXXXXXXX (8 digits)';
            }
        }
        
        // Validate name fields
        if (isset($rowData['first_name'])) {
            $firstName = trim($rowData['first_name']);
            if (strlen($firstName) < 2) {
                $errors['first_name'][] = 'First name must be at least 2 characters long';
            }
            if (!preg_match('/^[a-zA-Z\s\-\']+$/', $firstName)) {
                $errors['first_name'][] = 'First name contains invalid characters';
            }
        }
        
        if (isset($rowData['last_name'])) {
            $lastName = trim($rowData['last_name']);
            if (strlen($lastName) < 2) {
                $errors['last_name'][] = 'Last name must be at least 2 characters long';
            }
            if (!preg_match('/^[a-zA-Z\s\-\']+$/', $lastName)) {
                $errors['last_name'][] = 'Last name contains invalid characters';
            }
        }
        
        // Validate department
        if (isset($rowData['department'])) {
            $validDepartments = [
                'Engineering', 'Finance', 'Support', 'Customer Success', 
                'Human Resources', 'Marketing', 'Sales', 'Operations'
            ];
            if (!in_array($rowData['department'], $validDepartments)) {
                $errors['department'][] = 'Invalid department. Must be one of: ' . implode(', ', $validDepartments);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate multiple rows in batch
     */
    public function validateBatch(array $rowsData): array
    {
        $results = [];
        
        foreach ($rowsData as $index => $rowData) {
            $results[$index] = $this->validateRow($rowData);
        }
        
        return $results;
    }

    /**
     * Get validation summary for batch
     */
    public function getValidationSummary(array $validationResults): array
    {
        $totalRows = count($validationResults);
        $validRows = 0;
        $invalidRows = 0;
        $errorCounts = [];
        
        foreach ($validationResults as $result) {
            if ($result['valid']) {
                $validRows++;
            } else {
                $invalidRows++;
                
                foreach ($result['errors'] as $field => $errors) {
                    foreach ($errors as $error) {
                        $errorCounts[$error] = ($errorCounts[$error] ?? 0) + 1;
                    }
                }
            }
        }
        
        // Sort error counts by frequency (descending)
        arsort($errorCounts);
        
        return [
            'total_rows' => $totalRows,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'validation_rate' => $totalRows > 0 ? round(($validRows / $totalRows) * 100, 2) : 0,
            'error_counts' => $errorCounts,
            'most_common_errors' => array_slice($errorCounts, 0, 5, true)
        ];
    }

    /**
     * Sanitize row data
     */
    public function sanitizeRowData(array $rowData): array
    {
        $sanitized = [];
        
        foreach ($rowData as $key => $value) {
            if (is_string($value)) {
                // Trim whitespace
                $value = trim($value);
                
                // Convert to uppercase for specific fields
                if (in_array($key, ['currency', 'country_code'])) {
                    $value = strtoupper($value);
                }
                
                // Capitalize names
                if (in_array($key, ['first_name', 'last_name', 'department'])) {
                    $value = ucwords(strtolower($value));
                }
                
                // Format employee number
                if ($key === 'employee_number') {
                    $value = strtoupper($value);
                }
                
                // Format email
                if ($key === 'email') {
                    $value = strtolower($value);
                }
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }

    /**
     * Check for potential data quality issues
     */
    public function checkDataQuality(array $rowData): array
    {
        $warnings = [];
        
        // Check for suspicious salary values
        if (isset($rowData['salary'])) {
            $salary = (float) $rowData['salary'];
            if ($salary < 1000) {
                $warnings[] = 'Salary seems unusually low';
            }
            if ($salary > 1000000) {
                $warnings[] = 'Salary seems unusually high';
            }
        }
        
        // Check for suspicious start dates
        if (isset($rowData['start_date'])) {
            $startDate = \Carbon\Carbon::parse($rowData['start_date']);
            $age = $startDate->diffInYears(now());
            if ($age > 50) {
                $warnings[] = 'Start date is more than 50 years ago';
            }
        }
        
        // Check for duplicate-like names
        if (isset($rowData['first_name']) && isset($rowData['last_name'])) {
            if (strtolower($rowData['first_name']) === strtolower($rowData['last_name'])) {
                $warnings[] = 'First name and last name are identical';
            }
        }
        
        return $warnings;
    }
}
