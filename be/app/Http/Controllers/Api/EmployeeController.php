<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ImportJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Get all employees with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $department = $request->get('department');
            $country = $request->get('country');
            $search = $request->get('search');
            $importJobId = $request->get('import_job_id');
            
            $query = Employee::query();
            
            // Apply filters
            if ($department) {
                $query->where('department', $department);
            }
            
            if ($country) {
                $query->byCountry($country);
            }
            
            if ($importJobId) {
                $query->byImportJob($importJobId);
            }
            
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('employee_number', 'like', "%{$search}%");
                });
            }
            
            $employees = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            $data = $employees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'full_name' => $employee->full_name,
                    'email' => $employee->email,
                    'department' => $employee->department,
                    'salary' => $employee->salary,
                    'formatted_salary' => $employee->formatted_salary,
                    'currency' => $employee->currency,
                    'country_code' => $employee->country_code,
                    'start_date' => $employee->start_date->format('Y-m-d'),
                    'last_imported_at' => $employee->last_imported_at?->toISOString(),
                    'last_import_job_id' => $employee->last_import_job_id,
                    'created_at' => $employee->created_at->toISOString(),
                    'updated_at' => $employee->updated_at->toISOString(),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $employees->currentPage(),
                    'last_page' => $employees->lastPage(),
                    'per_page' => $employees->perPage(),
                    'total' => $employees->total(),
                ],
                'filters' => [
                    'departments' => $this->getDepartments(),
                    'countries' => $this->getCountries(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific employee
     */
    public function show(string $id): JsonResponse
    {
        try {
            $employee = Employee::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'full_name' => $employee->full_name,
                    'email' => $employee->email,
                    'department' => $employee->department,
                    'salary' => $employee->salary,
                    'formatted_salary' => $employee->formatted_salary,
                    'currency' => $employee->currency,
                    'country_code' => $employee->country_code,
                    'start_date' => $employee->start_date->format('Y-m-d'),
                    'last_imported_at' => $employee->last_imported_at?->toISOString(),
                    'last_import_job_id' => $employee->last_import_job_id,
                    'created_at' => $employee->created_at->toISOString(),
                    'updated_at' => $employee->updated_at->toISOString(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Delete a specific employee
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all employees (for demo purposes)
     */
    public function clearAll(): JsonResponse
    {
        try {
            DB::transaction(function () {
                // Delete all employees
                Employee::truncate();
                
                // Also clear import jobs and related data for clean demo
                ImportJob::truncate();
            });
            
            return response()->json([
                'success' => true,
                'message' => 'All employees and import data cleared successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available departments
     */
    private function getDepartments(): array
    {
        return Employee::distinct()
            ->pluck('department')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get available countries
     */
    private function getCountries(): array
    {
        return Employee::distinct()
            ->pluck('country_code')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }
}
