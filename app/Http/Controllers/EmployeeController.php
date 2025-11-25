<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Get All Employees
     */
    public function index(Request $request)
    {
        $query = Employee::withCount('customers');

        // البحث
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('position', 'LIKE', "%{$search}%");
            });
        }

        // التصفية حسب القسم
        if ($request->has('department')) {
            $query->byDepartment($request->department);
        }

        // التصفية حسب الحالة
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // الترتيب
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $employees = $query->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    /**
     * Create New Employee
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'required|string|unique:employees,phone',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,on_leave',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'avatar' => 'nullable|string',
            'permissions' => 'nullable|array'
        ]);

        $employee = Employee::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully',
            'data' => $employee
        ], 201);
    }

    /**
     * Get Employee Details
     */
    public function show($id)
    {
        $employee = Employee::with(['customers' => function ($query) {
            $query->with(['orders', 'segment']);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => $employee,
                'statistics' => [
                    'customers_count' => $employee->customers_count,
                    'active_customers' => $employee->active_customers_count,
                    'total_sales' => $employee->total_sales,
                    'years_of_service' => $employee->years_of_service
                ]
            ]
        ]);
    }

    /**
     * Update Employee
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:employees,email,' . $id,
            'phone' => 'sometimes|string|unique:employees,phone,' . $id,
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,on_leave',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'avatar' => 'nullable|string',
            'permissions' => 'nullable|array'
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully',
            'data' => $employee
        ]);
    }

    /**
     * Delete Employee
     */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        
        // عدد العملاء المرتبطين
        $customersCount = $employee->customers()->count();
        
        if ($customersCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete employee. {$customersCount} customers are assigned to this employee. Please reassign them first."
            ], 400);
        }

        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully'
        ]);
    }

    /**
     * Get Employee's Customers
     */
    public function customers($id, Request $request)
    {
        $employee = Employee::findOrFail($id);
        
        $query = $employee->customers()->with(['segment', 'orders']);

        // البحث
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'position' => $employee->position
            ],
            'data' => $customers
        ]);
    }

    /**
     * Assign Customer to Employee
     */
    public function assignCustomer(Request $request, $employeeId)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id'
        ]);

        $employee = Employee::findOrFail($employeeId);
        
        if (!$employee->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign customers to inactive employee'
            ], 400);
        }

        $customer = Customer::findOrFail($validated['customer_id']);
        
        $previousEmployee = $customer->employee;
        
        $customer->employee_id = $employeeId;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Customer assigned successfully',
            'data' => [
                'customer' => $customer->load('employee'),
                'previous_employee' => $previousEmployee ? [
                    'id' => $previousEmployee->id,
                    'name' => $previousEmployee->name
                ] : null,
                'new_employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name
                ]
            ]
        ]);
    }

    /**
     * Unassign Customer from Employee
     */
    public function unassignCustomer($employeeId, $customerId)
    {
        $employee = Employee::findOrFail($employeeId);
        $customer = Customer::where('id', $customerId)
            ->where('employee_id', $employeeId)
            ->firstOrFail();

        $customer->employee_id = null;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Customer unassigned successfully',
            'data' => $customer
        ]);
    }

    /**
     * Assign Multiple Customers to Employee
     */
    public function assignMultipleCustomers(Request $request, $employeeId)
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id'
        ]);

        $employee = Employee::findOrFail($employeeId);

        if (!$employee->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign customers to inactive employee'
            ], 400);
        }

        $assignedCount = Customer::whereIn('id', $validated['customer_ids'])
            ->update(['employee_id' => $employeeId]);

        return response()->json([
            'success' => true,
            'message' => "{$assignedCount} customers assigned successfully",
            'data' => [
                'employee_id' => $employeeId,
                'assigned_count' => $assignedCount
            ]
        ]);
    }

    /**
     * Get Employee Statistics
     */
    public function statistics($id)
    {
        $employee = Employee::withCount('customers')->findOrFail($id);

        $stats = [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'position' => $employee->position,
                'department' => $employee->department
            ],
            'customers' => [
                'total' => $employee->customers_count,
                'active' => $employee->active_customers_count,
                'with_orders' => $employee->customers()->has('orders')->count(),
                'without_orders' => $employee->customers()->doesntHave('orders')->count()
            ],
            'orders' => [
                'total' => $employee->customers()->withCount('orders')->get()->sum('orders_count'),
                'completed' => $employee->customers()
                    ->join('orders', 'customers.id', '=', 'orders.customer_id')
                    ->where('orders.status', 'completed')
                    ->count(),
                'pending' => $employee->customers()
                    ->join('orders', 'customers.id', '=', 'orders.customer_id')
                    ->where('orders.status', 'pending')
                    ->count()
            ],
            'sales' => [
                'total' => $employee->total_sales,
                'average_per_customer' => $employee->customers_count > 0 
                    ? round($employee->total_sales / $employee->customers_count, 2) 
                    : 0
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get All Employees Statistics (Dashboard)
     */
    public function dashboardStats()
    {
        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::active()->count(),
            'inactive_employees' => Employee::where('status', 'inactive')->count(),
            'on_leave' => Employee::where('status', 'on_leave')->count(),
            'total_customers_assigned' => Customer::whereNotNull('employee_id')->count(),
            'unassigned_customers' => Customer::whereNull('employee_id')->count(),
            'employees_by_department' => Employee::select('department', DB::raw('count(*) as count'))
                ->groupBy('department')
                ->get(),
            'top_performers' => Employee::withCount('customers')
                ->orderBy('customers_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($emp) {
                    return [
                        'id' => $emp->id,
                        'name' => $emp->name,
                        'position' => $emp->position,
                        'customers_count' => $emp->customers_count,
                        'total_sales' => $emp->total_sales
                    ];
                })
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Update Employee Status
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,on_leave'
        ]);

        $employee = Employee::findOrFail($id);
        $employee->status = $validated['status'];
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Employee status updated successfully',
            'data' => $employee
        ]);
    }
}