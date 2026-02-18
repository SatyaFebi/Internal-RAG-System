<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AIController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/ingest', [AIController::class, 'store']);
    Route::post('/chat', [AIController::class, 'chat']);
});

