<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware('throttle:30,1')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});
Route::prefix('public')->middleware('throttle:120,1')->group(function (): void {
    Route::apiResource('users', UserController::class)->only(['index', 'show'])->names(['index' => 'public.users.index', 'show' => 'public.users.show']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show'])->names(['index' => 'public.products.index', 'show' => 'public.products.show']);
});
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('my/sales', [SaleController::class, 'mine']);
    Route::prefix('private')->name('private.')->middleware('admin')->group(function (): void {
        Route::apiResource('users', UserController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('sales', SaleController::class);
    });
});
