<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'import_job_id',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'start_date' => 'date',
    ];

    // Relationships
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class, 'import_job_id', 'uuid');
    }

    // Scopes
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeBySalaryRange($query, float $min, float $max)
    {
        return $query->whereBetween('salary', [$min, $max]);
    }

    public function scopeByImportJob($query, string $importJobId)
    {
        return $query->where('import_job_id', $importJobId);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getSalaryFormattedAttribute()
    {
        return $this->currency . ' ' . number_format($this->salary, 2);
    }

    // Validation rules
    public static function getValidationRules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'department' => ['required', 'string', 'max:100'],
            'salary' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'country_code' => ['required', 'string', 'size:2'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}