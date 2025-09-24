<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\Rule;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'department',
        'salary',
        'currency',
        'country_code',
        'start_date',
        'last_imported_at',
        'last_import_job_id',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'start_date' => 'date',
        'last_imported_at' => 'datetime',
    ];

    protected $dates = [
        'start_date',
        'last_imported_at',
    ];

    /**
     * Get validation rules for employee data
     */
    public static function getValidationRules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:255', 'regex:/^EMP-\d+$/'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * Get unique validation rules for updates
     */
    public static function getUniqueValidationRules(?int $excludeId = null): array
    {
        $rules = self::getValidationRules();
        
        $rules['employee_number'][] = Rule::unique('employees', 'employee_number')->ignore($excludeId);
        $rules['email'][] = Rule::unique('employees', 'email')->ignore($excludeId);
        
        return $rules;
    }

    /**
     * Find employee by employee number or email for upsert operations
     */
    public static function findForUpsert(string $employeeNumber, string $email): ?self
    {
        return self::where('employee_number', $employeeNumber)
            ->orWhere('email', $email)
            ->first();
    }

    /**
     * Upsert employee data (create or update)
     */
    public static function upsertEmployee(array $data, string $importJobId): self
    {
        $existing = self::findForUpsert($data['employee_number'], $data['email']);
        
        if ($existing) {
            // Update existing employee
            $existing->update([
                ...$data,
                'last_imported_at' => now(),
                'last_import_job_id' => $importJobId,
            ]);
            return $existing;
        }
        
        // Create new employee
        return self::create([
            ...$data,
            'last_imported_at' => now(),
            'last_import_job_id' => $importJobId,
        ]);
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get formatted salary attribute
     */
    public function getFormattedSalaryAttribute(): string
    {
        return number_format($this->salary, 2) . ' ' . $this->currency;
    }


    /**
     * Scope for filtering by country
     */
    public function scopeByCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope for employees imported in a specific job
     */
    public function scopeByImportJob($query, string $importJobId)
    {
        return $query->where('last_import_job_id', $importJobId);
    }
}
