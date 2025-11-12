<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerProgressController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\EmailController;

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