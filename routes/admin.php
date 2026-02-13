<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Admin routes - protected by authentication and admin role
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // User management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::post('/{user}/assign-role', [UserController::class, 'assignRole']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });
});
