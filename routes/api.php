<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerProgressController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;

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
});



/*
|--------------------------------------------------------------------------
| Orders Routes
|--------------------------------------------------------------------------
*/

// Orders CRUD
Route::prefix('orders')->group(function () {
    // Main CRUD operations
    Route::get('/', [OrderController::class, 'index']); // GET /api/orders
    Route::post('/', [OrderController::class, 'store']); // POST /api/orders
    Route::get('/{id}', [OrderController::class, 'show']); // GET /api/orders/{id}
    Route::put('/{id}', [OrderController::class, 'update']); // PUT /api/orders/{id}
    Route::delete('/{id}', [OrderController::class, 'destroy']); // DELETE /api/orders/{id}
    
    // Order status operations
    Route::patch('/{id}/status', [OrderController::class, 'updateStatus']); // PATCH /api/orders/{id}/status
    Route::patch('/{id}/payment-status', [OrderController::class, 'updatePaymentStatus']); // PATCH /api/orders/{id}/payment-status
    
    // Statistics
    Route::get('/statistics/all', [OrderController::class, 'statistics']); // GET /api/orders/statistics/all
    
    // Order Items nested routes
    Route::prefix('{orderId}/items')->group(function () {
        Route::get('/', [OrderItemController::class, 'index']); // GET /api/orders/{orderId}/items
        Route::post('/', [OrderItemController::class, 'store']); // POST /api/orders/{orderId}/items
        Route::get('/{itemId}', [OrderItemController::class, 'show']); // GET /api/orders/{orderId}/items/{itemId}
        Route::put('/{itemId}', [OrderItemController::class, 'update']); // PUT /api/orders/{orderId}/items/{itemId}
        Route::delete('/{itemId}', [OrderItemController::class, 'destroy']); // DELETE /api/orders/{orderId}/items/{itemId}
        
        // Item specific operations
        Route::patch('/{itemId}/progress', [OrderItemController::class, 'updateProgress']); // PATCH /api/orders/{orderId}/items/{itemId}/progress
        Route::patch('/{itemId}/status', [OrderItemController::class, 'updateStatus']); // PATCH /api/orders/{orderId}/items/{itemId}/status
    });
});