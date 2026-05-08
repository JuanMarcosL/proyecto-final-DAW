<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AbsenceController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('work-orders', WorkOrderController::class);
    Route::apiResource('resources', ResourceController::class);
    Route::apiResource('appointments', AppointmentController::class);
    Route::apiResource('absences', AbsenceController::class);
});
