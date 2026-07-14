<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// Auth (no token required for login)
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Services — /services/generate must be before /{service} to avoid ambiguity
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services/generate', [ServiceController::class, 'generate']);
    Route::get('/services/{service}', [ServiceController::class, 'show']);
    Route::get('/services/{service}/tracking', [TrackingController::class, 'history']);

    // Tracking
    Route::get('/tracking/latest', [TrackingController::class, 'latest']);

    // Simulation — delegates to the DDD microservice
    Route::post('/simulation/start', [SimulationController::class, 'start']);
    Route::post('/simulation/stop', [SimulationController::class, 'stop']);
    Route::get('/simulation/status', [SimulationController::class, 'status']);
});
