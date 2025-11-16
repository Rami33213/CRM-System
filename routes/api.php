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