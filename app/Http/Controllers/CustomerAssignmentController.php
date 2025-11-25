<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Http\Request;

class CustomerAssignmentController extends Controller
{
    /**
     * Get Unassigned Customers (عملاء بدون موظف)
     */
    public function unassignedCustomers(Request $request)
    {
        $query = Customer::whereNull('employee_id')
            ->with('segment');

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
            'data' => $customers,
            'summary' => [
                'total_unassigned' => Customer::whereNull('employee_id')->count()
            ]
        ]);
    }

    /**
     * Assign Customer to Employee (من واجهة الـ Admin)
     */
    public function assignToEmployee(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'employee_id' => 'required|exists:employees,id'
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $employee = Employee::findOrFail($validated['employee_id']);

        if (!$employee->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign customer to inactive employee'
            ], 400);
        }

        $previousEmployee = $customer->employee;

        $customer->employee_id = $validated['employee_id'];
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Customer assigned to employee successfully',
            'data' => [
                'customer' => $customer->load('employee'),
                'previous_employee' => $previousEmployee,
                'new_employee' => $employee
            ]
        ]);
    }

    /**
     * Reassign Customer to Another Employee
     */
    public function reassignCustomer(Request $request, $customerId)
    {
        $validated = $request->validate([
            'new_employee_id' => 'required|exists:employees,id'
        ]);

        $customer = Customer::findOrFail($customerId);
        $oldEmployee = $customer->employee;
        $newEmployee = Employee::findOrFail($validated['new_employee_id']);

        if (!$newEmployee->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reassign customer to inactive employee'
            ], 400);
        }

        $customer->employee_id = $validated['new_employee_id'];
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Customer reassigned successfully',
            'data' => [
                'customer' => $customer->load('employee'),
                'old_employee' => $oldEmployee ? [
                    'id' => $oldEmployee->id,
                    'name' => $oldEmployee->name
                ] : null,
                'new_employee' => [
                    'id' => $newEmployee->id,
                    'name' => $newEmployee->name
                ]
            ]
        ]);
    }

    /**
     * Remove Employee from Customer (فك الربط)
     */
    public function removeEmployeeFromCustomer($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        $previousEmployee = $customer->employee;

        $customer->employee_id = null;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Employee removed from customer successfully',
            'data' => [
                'customer' => $customer,
                'removed_employee' => $previousEmployee
            ]
        ]);
    }

    /**
     * Bulk Assign Customers to Employee
     */
    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
            'employee_id' => 'required|exists:employees,id'
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        if (!$employee->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign customers to inactive employee'
            ], 400);
        }

        $updated = Customer::whereIn('id', $validated['customer_ids'])
            ->update(['employee_id' => $validated['employee_id']]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} customers assigned successfully",
            'data' => [
                'employee' => $employee,
                'assigned_count' => $updated
            ]
        ]);
    }

    /**
     * Bulk Remove Employee from Customers
     */
    public function bulkUnassign(Request $request)
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id'
        ]);

        $updated = Customer::whereIn('id', $validated['customer_ids'])
            ->update(['employee_id' => null]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} customers unassigned successfully",
            'data' => [
                'unassigned_count' => $updated
            ]
        ]);
    }

    /**
     * Transfer All Customers from One Employee to Another
     */
    public function transferCustomers(Request $request)
    {
        $validated = $request->validate([
            'from_employee_id' => 'required|exists:employees,id',
            'to_employee_id' => 'required|exists:employees,id'
        ]);

        if ($validated['from_employee_id'] === $validated['to_employee_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot transfer to the same employee'
            ], 400);
        }

        $fromEmployee = Employee::findOrFail($validated['from_employee_id']);
        $toEmployee = Employee::findOrFail($validated['to_employee_id']);

        if (!$toEmployee->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot transfer customers to inactive employee'
            ], 400);
        }

        $transferred = Customer::where('employee_id', $validated['from_employee_id'])
            ->update(['employee_id' => $validated['to_employee_id']]);

        return response()->json([
            'success' => true,
            'message' => "{$transferred} customers transferred successfully",
            'data' => [
                'from_employee' => [
                    'id' => $fromEmployee->id,
                    'name' => $fromEmployee->name,
                    'remaining_customers' => 0
                ],
                'to_employee' => [
                    'id' => $toEmployee->id,
                    'name' => $toEmployee->name,
                    'total_customers' => $toEmployee->customers()->count()
                ],
                'transferred_count' => $transferred
            ]
        ]);
    }

    /**
     * Get Assignment Overview
     */
    public function assignmentOverview()
    {
        $employees = Employee::withCount('customers')->get();
        
        $overview = [
            'total_customers' => Customer::count(),
            'assigned_customers' => Customer::whereNotNull('employee_id')->count(),
            'unassigned_customers' => Customer::whereNull('employee_id')->count(),
            'employees' => $employees->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->name,
                    'position' => $emp->position,
                    'status' => $emp->status,
                    'customers_count' => $emp->customers_count,
                    'workload_percentage' => Customer::count() > 0 
                        ? round(($emp->customers_count / Customer::count()) * 100, 2) 
                        : 0
                ];
            }),
            'distribution' => [
                'average_per_employee' => $employees->count() > 0 
                    ? round(Customer::whereNotNull('employee_id')->count() / $employees->where('status', 'active')->count(), 2) 
                    : 0,
                'max_customers' => $employees->max('customers_count'),
                'min_customers' => $employees->min('customers_count')
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $overview
        ]);
    }
}