<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HelloController;
use App\Http\Controllers\ModeratorController;
use App\Http\Controllers\ProtectedController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:api')->get('/auth/me', [AuthController::class, 'me']);
Route::get('/hello', HelloController::class);

Route::middleware('auth:api')->get('/protected', ProtectedController::class);

// Moderator routes - protected by authentication and lock users permission
Route::middleware(['auth:api', 'permission:lock users'])->prefix('moderation')->group(function () {
    Route::post('/users/{user}/lock', [ModeratorController::class, 'lockUser']);
    Route::post('/users/{user}/unlock', [ModeratorController::class, 'unlockUser']);
    Route::get('/users/locked', [ModeratorController::class, 'lockedUsers']);
});
