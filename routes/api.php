<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HelloController;
use App\Http\Controllers\ProtectedController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/hello', HelloController::class);

Route::middleware('auth:api')->get('/protected', ProtectedController::class);
