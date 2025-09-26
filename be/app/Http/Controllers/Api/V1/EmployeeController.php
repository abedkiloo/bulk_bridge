<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Http\Resources\EmployeeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/employees",
     *     summary="Get all employees with filtering and pagination",
     *     @OA\Parameter(name="page", in="query", description="Page number", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="department", in="query", description="Filter by department", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort", in="query", description="Sort field", @OA\Schema(type="string")),
     *     @OA\Parameter(name="order", in="query", description="Sort order (asc/desc)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Employees list")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Employee::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->has('department') && $request->department) {
            $query->where('department', $request->department);
        }


        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 50), 100); // Max 100 per page
        $employees = $query->paginate($perPage);

        return response()->json([
            'data' => EmployeeResource::collection($employees->items()),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'last_page' => $employees->lastPage(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
            ],
            'links' => [
                'first' => $employees->url(1),
                'last' => $employees->url($employees->lastPage()),
                'prev' => $employees->previousPageUrl(),
                'next' => $employees->nextPageUrl(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/employees/{id}",
     *     summary="Get specific employee",
     *     @OA\Parameter(name="id", in="path", required=true, description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Employee details"),
     *     @OA\Response(response=404, description="Employee not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found'
            ], 404);
        }

        return response()->json([
            'data' => new EmployeeResource($employee)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/employees/{id}",
     *     summary="Update employee",
     *     @OA\Parameter(name="id", in="path", required=true, description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/EmployeeUpdate")),
     *     @OA\Response(response=200, description="Employee updated"),
     *     @OA\Response(response=404, description="Employee not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found'
            ], 404);
        }

        $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:employees,email,' . $id,
            'department' => 'sometimes|string|max:255',
            'salary' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date|before:today',
        ]);

        $employee->update($request->only([
            'first_name', 'last_name', 'email', 'department', 
            'salary', 'start_date'
        ]));

        return response()->json([
            'message' => 'Employee updated successfully',
            'data' => new EmployeeResource($employee->fresh())
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/employees/{id}",
     *     summary="Delete employee",
     *     @OA\Parameter(name="id", in="path", required=true, description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Employee deleted"),
     *     @OA\Response(response=404, description="Employee not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found'
            ], 404);
        }

        $employee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/employees/departments",
     *     summary="Get all departments",
     *     @OA\Response(response=200, description="Departments list")
     * )
     */
    public function departments(): JsonResponse
    {
        $departments = Employee::select('department')
            ->distinct()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->orderBy('department')
            ->pluck('department');

        return response()->json([
            'data' => $departments
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/employees/statistics",
     *     summary="Get employee statistics",
     *     @OA\Response(response=200, description="Employee statistics")
     * )
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_employees' => Employee::count(),
            'departments_count' => Employee::distinct('department')->count('department'),
            'average_salary' => Employee::whereNotNull('salary')->avg('salary'),
            'total_salary_cost' => Employee::whereNotNull('salary')->sum('salary'),
        ];

        // Department breakdown
        $departmentStats = Employee::select('department', DB::raw('count(*) as count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'data' => [
                'overview' => $stats,
                'departments' => $departmentStats
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/employees/clear-all",
     *     summary="Clear all employees (demo/reset)",
     *     @OA\Response(response=200, description="All employees cleared")
     * )
     */
    public function clearAll(): JsonResponse
    {
        $count = Employee::count();
        Employee::truncate();

        return response()->json([
            'message' => "All {$count} employees have been cleared successfully"
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/employees/bulk-update",
     *     summary="Bulk update employees",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="employee_ids", type="array", @OA\Items(type="integer")),
     *         @OA\Property(property="updates", type="object")
     *     )),
     *     @OA\Response(response=200, description="Employees updated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'updates' => 'required|array',
            'updates.department' => 'sometimes|string|max:255',
        ]);

        $updated = Employee::whereIn('id', $request->employee_ids)
            ->update($request->updates);

        return response()->json([
            'message' => "Successfully updated {$updated} employees",
            'updated_count' => $updated
        ]);
    }
}
