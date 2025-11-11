<?php
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerProgressController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmailController;


Route::prefix('v1')->group(function () {
    // Customer routes
    Route::apiResource('customers', CustomerController::class);
    
    // Customer progress routes
    Route::prefix('customers/{customerId}/progress')->group(function () {
        Route::get('/', [CustomerProgressController::class, 'getByCustomer']);
    });
    
    Route::apiResource('progress', CustomerProgressController::class)
        ->only(['store', 'update', 'destroy']);
});
