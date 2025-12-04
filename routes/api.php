<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerProgressController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\order\ServiceController;
use App\Http\Controllers\order\OrderController;
use App\Http\Controllers\order\OrderItemController;
use App\Http\Controllers\CustomerDatatableController;
use App\Http\Controllers\CustomerExportController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CustomerAssignmentController;
use App\Http\Controllers\tag\TagController;
use App\Http\Controllers\tag\CustomerTagController;


Route::middleware('api')->prefix('v1')->group(function () {
    // Customer Routes
    Route::apiResource('customers', CustomerController::class);

    // Customer Progress Routes
    Route::prefix('customers/{customerId}/progress')->group(function () {
        Route::get('/', [CustomerProgressController::class, 'getByCustomer']);
    });
    Route::apiResource('progress', CustomerProgressController::class)
        ->only(['store', 'update', 'destroy']);

    // Messages Routes
    Route::prefix('customers/{customerId}/messages')->group(function () {
        Route::get('/', [MessageController::class, 'getByCustomer']);
    });
    Route::apiResource('messages', MessageController::class)
        ->only(['store', 'destroy']);
    Route::put('/messages/{id}/read', [MessageController::class, 'markAsRead']);

    // Emails Routes
    Route::prefix('customers/{customerId}/emails')->group(function () {
        Route::get('/', [EmailController::class, 'getByCustomer']);
    });
    Route::apiResource('emails', EmailController::class)
        ->only(['store', 'destroy']);
    Route::put('/emails/{id}/read', [EmailController::class, 'markAsRead']);


    
// Services Routes
Route::prefix('services')->group(function () {
    Route::get('/', [ServiceController::class, 'index']); // GET /api/services
    Route::post('/', [ServiceController::class, 'store']); // POST /api/services
    Route::get('/{id}', [ServiceController::class, 'show']); // GET /api/services/{id}
    Route::put('/{id}', [ServiceController::class, 'update']); // PUT /api/services/{id}
    Route::delete('/{id}', [ServiceController::class, 'destroy']); // DELETE /api/services/{id}
    Route::post('/{id}/restore', [ServiceController::class, 'restore']); // POST /api/services/{id}/restore
    Route::patch('/{id}/toggle-active', [ServiceController::class, 'toggleActive']); // PATCH /api/services/{id}/toggle-active
    Route::get('/{id}/stats', [ServiceController::class, 'stats']); // GET /api/services/{id}/stats
});

// Orders Routes
Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']); // GET /api/orders
    Route::post('/', [OrderController::class, 'store']); // POST /api/orders
    Route::get('/stats', [OrderController::class, 'stats']); // GET /api/orders/stats
    Route::get('/{id}', [OrderController::class, 'show']); // GET /api/orders/{id}
    Route::put('/{id}', [OrderController::class, 'update']); // PUT /api/orders/{id}
    Route::delete('/{id}', [OrderController::class, 'destroy']); // DELETE /api/orders/{id}
    Route::patch('/{id}/status', [OrderController::class, 'updateStatus']); // PATCH /api/orders/{id}/status
    Route::post('/{id}/payment', [OrderController::class, 'addPayment']); // POST /api/orders/{id}/payment
    
    // Order Items Routes (nested)
    Route::get('/{orderId}/items', [OrderItemController::class, 'index']); // GET /api/orders/{orderId}/items
    Route::post('/{orderId}/items', [OrderItemController::class, 'store']); // POST /api/orders/{orderId}/items
    Route::put('/{orderId}/items/{itemId}', [OrderItemController::class, 'update']); // PUT /api/orders/{orderId}/items/{itemId}
    Route::delete('/{orderId}/items/{itemId}', [OrderItemController::class, 'destroy']); // DELETE /api/orders/{orderId}/items/{itemId}
    Route::patch('/{orderId}/items/{itemId}/status', [OrderItemController::class, 'updateStatus']); // PATCH /api/orders/{orderId}/items/{itemId}/status
});


Route::prefix('customers')->group(function () {
    
    // DataTable Endpoints
    Route::get('/datatable', [CustomerDatatableController::class, 'index']); 
    // GET /api/customers/datatable
    // Query Params: search, segment_id, min_score, max_score, date_from, date_to, 
    //               sort_by, sort_direction, per_page, page
    
    Route::get('/datatable/{id}', [CustomerDatatableController::class, 'show']); 
    // GET /api/customers/datatable/{id} - View customer details with orders
    
    Route::post('/datatable/{id}/sync-score', [CustomerDatatableController::class, 'syncScoreFromQuiz']); 
    // POST /api/customers/datatable/{id}/sync-score - Sync score from quiz
    
    Route::post('/datatable/sync-all-scores', [CustomerDatatableController::class, 'syncAllScores']); 
    // POST /api/customers/datatable/sync-all-scores - Sync all customers scores
    
    Route::get('/datatable/stats/overview', [CustomerDatatableController::class, 'statistics']); 
    // GET /api/customers/datatable/stats/overview - Dashboard statistics
    
    // Export Endpoints
    Route::get('/export/csv', [CustomerExportController::class, 'exportCSV']); 
    // GET /api/customers/export/csv - Export to CSV
    
    Route::get('/export/excel', [CustomerExportController::class, 'exportExcel']); 
    // GET /api/customers/export/excel - Export to Excel (XLSX)
    
    Route::get('/export/json', [CustomerExportController::class, 'exportJSON']); 
    // GET /api/customers/export/json - Export to JSON
    
    Route::get('/export/{id}/details', [CustomerExportController::class, 'exportCustomerDetails']); 
    // GET /api/customers/export/{id}/details - Export single customer with orders
});


Route::prefix('employees')->group(function () {
    
    // Employee CRUD
    Route::get('/', [EmployeeController::class, 'index']); 
    // GET /api/employees - Get all employees
    
    Route::post('/', [EmployeeController::class, 'store']); 
    // POST /api/employees - Create new employee
    
    Route::get('/{id}', [EmployeeController::class, 'show']); 
    // GET /api/employees/{id} - Get employee details
    
    Route::put('/{id}', [EmployeeController::class, 'update']); 
    // PUT /api/employees/{id} - Update employee
    
    Route::delete('/{id}', [EmployeeController::class, 'destroy']); 
    // DELETE /api/employees/{id} - Delete employee
    
    // Employee Status
    Route::patch('/{id}/status', [EmployeeController::class, 'updateStatus']); 
    // PATCH /api/employees/{id}/status - Update employee status
    
    // Employee Customers
    Route::get('/{id}/customers', [EmployeeController::class, 'customers']); 
    // GET /api/employees/{id}/customers - Get employee's customers
    
    Route::post('/{id}/assign-customer', [EmployeeController::class, 'assignCustomer']); 
    // POST /api/employees/{id}/assign-customer - Assign customer to employee
    
    Route::delete('/{employeeId}/customers/{customerId}', [EmployeeController::class, 'unassignCustomer']); 
    // DELETE /api/employees/{employeeId}/customers/{customerId} - Unassign customer
    
    Route::post('/{id}/assign-multiple', [EmployeeController::class, 'assignMultipleCustomers']); 
    // POST /api/employees/{id}/assign-multiple - Assign multiple customers
    
    // Statistics
    Route::get('/{id}/statistics', [EmployeeController::class, 'statistics']); 
    // GET /api/employees/{id}/statistics - Get employee statistics
    
    Route::get('/stats/dashboard', [EmployeeController::class, 'dashboardStats']); 
    // GET /api/employees/stats/dashboard - Dashboard statistics
});

/*
|--------------------------------------------------------------------------
| Customer Assignment Routes (Admin Panel)
|--------------------------------------------------------------------------
*/

Route::prefix('admin/customer-assignment')->group(function () {
    
    // View Unassigned Customers
    Route::get('/unassigned', [CustomerAssignmentController::class, 'unassignedCustomers']); 
    // GET /api/admin/customer-assignment/unassigned
    
    // Assign Customer to Employee
    Route::post('/assign', [CustomerAssignmentController::class, 'assignToEmployee']); 
    // POST /api/admin/customer-assignment/assign
    // Body: { customer_id, employee_id }
    
    // Reassign Customer
    Route::put('/customers/{customerId}/reassign', [CustomerAssignmentController::class, 'reassignCustomer']); 
    // PUT /api/admin/customer-assignment/customers/{customerId}/reassign
    // Body: { new_employee_id }
    
    // Remove Employee from Customer
    Route::delete('/customers/{customerId}/remove-employee', [CustomerAssignmentController::class, 'removeEmployeeFromCustomer']); 
    // DELETE /api/admin/customer-assignment/customers/{customerId}/remove-employee
    
    // Bulk Operations
    Route::post('/bulk-assign', [CustomerAssignmentController::class, 'bulkAssign']); 
    // POST /api/admin/customer-assignment/bulk-assign
    // Body: { customer_ids: [], employee_id }
    
    Route::post('/bulk-unassign', [CustomerAssignmentController::class, 'bulkUnassign']); 
    // POST /api/admin/customer-assignment/bulk-unassign
    // Body: { customer_ids: [] }
    
    // Transfer Customers
    Route::post('/transfer', [CustomerAssignmentController::class, 'transferCustomers']); 
    // POST /api/admin/customer-assignment/transfer
    // Body: { from_employee_id, to_employee_id }
    
    // Overview
    Route::get('/overview', [CustomerAssignmentController::class, 'assignmentOverview']); 
    // GET /api/admin/customer-assignment/overview
});



/*
|--------------------------------------------------------------------------
| Tags Routes
|--------------------------------------------------------------------------
*/

Route::prefix('tags')->group(function () {
    
    // CRUD للتاغات
    Route::get('/', [TagController::class, 'index']); 
    // GET /api/tags - Get all tags
    
    Route::post('/', [TagController::class, 'store']); 
    // POST /api/tags - Create new tag
    
    Route::get('/{id}', [TagController::class, 'show']); 
    // GET /api/tags/{id} - Get tag details with customers
    
    Route::put('/{id}', [TagController::class, 'update']); 
    // PUT /api/tags/{id} - Update tag
    
    Route::delete('/{id}', [TagController::class, 'destroy']); 
    // DELETE /api/tags/{id} - Delete tag
    
    // Additional Endpoints
    Route::get('/popular/list', [TagController::class, 'popular']); 
    // GET /api/tags/popular/list - Get popular tags
    
    Route::post('/bulk-delete', [TagController::class, 'bulkDestroy']); 
    // POST /api/tags/bulk-delete - Delete multiple tags
});

/*
|--------------------------------------------------------------------------
| Customer Tags Routes
|--------------------------------------------------------------------------
*/

Route::prefix('customers')->group(function () {
    
    // Get Customer's Tags
    Route::get('/{customerId}/tags', [CustomerTagController::class, 'index']); 
    // GET /api/customers/{customerId}/tags
    
    // Attach Tag to Customer
    Route::post('/{customerId}/tags', [CustomerTagController::class, 'attach']); 
    // POST /api/customers/{customerId}/tags
    // Body: { tag_id: 1 }
    
    // Attach Multiple Tags
    Route::post('/{customerId}/tags/attach-multiple', [CustomerTagController::class, 'attachMultiple']); 
    // POST /api/customers/{customerId}/tags/attach-multiple
    // Body: { tag_ids: [1, 2, 3] }
    
    // Detach Tag from Customer
    Route::delete('/{customerId}/tags/{tagId}', [CustomerTagController::class, 'detach']); 
    // DELETE /api/customers/{customerId}/tags/{tagId}
    
    // Sync Tags (استبدال كل التاغات)
    Route::put('/{customerId}/tags/sync', [CustomerTagController::class, 'sync']); 
    // PUT /api/customers/{customerId}/tags/sync
    // Body: { tag_ids: [1, 2, 3] }
    
    // Detach All Tags
    Route::delete('/{customerId}/tags', [CustomerTagController::class, 'detachAll']); 
    // DELETE /api/customers/{customerId}/tags
    
    // Create Tag and Attach (في خطوة واحدة)
    Route::post('/{customerId}/tags/create-and-attach', [CustomerTagController::class, 'createAndAttach']); 
    // POST /api/customers/{customerId}/tags/create-and-attach
    // Body: { name: "ويب سايت أخضر", color: "#22C55E" }
});

/*
|--------------------------------------------------------------------------
| Search by Tags Routes
|--------------------------------------------------------------------------
*/

Route::prefix('search')->group(function () {
    
    // Search Customers by Tag
    Route::get('/customers-by-tag', [CustomerTagController::class, 'searchByTag']); 
    // GET /api/search/customers-by-tag?tag_id=1
    // GET /api/search/customers-by-tag?tag_name=ويب سايت
    // GET /api/search/customers-by-tag?tag_ids[]=1&tag_ids[]=2
    
    // Search Customers by ALL Tags (AND logic)
    Route::get('/customers-by-all-tags', [CustomerTagController::class, 'searchByAllTags']); 
    // GET /api/search/customers-by-all-tags?tag_ids[]=1&tag_ids[]=2
});

});

