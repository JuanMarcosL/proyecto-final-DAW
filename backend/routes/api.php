<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AbsenceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Work Orders — técnicos solo pueden ver y actualizar estado
    Route::get('work-orders', [WorkOrderController::class, 'index']);
    Route::get('work-orders/{workOrder}', [WorkOrderController::class, 'show']);
    Route::post('work-orders', [WorkOrderController::class, 'store'])->middleware('role:admin,supervisor');
    Route::put('work-orders/{workOrder}', [WorkOrderController::class, 'update']);
    Route::patch('work-orders/{workOrder}', [WorkOrderController::class, 'update']);
    Route::delete('work-orders/{workOrder}', [WorkOrderController::class, 'destroy'])->middleware('role:admin,supervisor');

    // Appointments — técnicos solo ven, admin y supervisor gestionan
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
    Route::post('appointments', [AppointmentController::class, 'store'])->middleware('role:admin,supervisor');
    Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->middleware('role:admin,supervisor');
    Route::patch('appointments/{appointment}', [AppointmentController::class, 'update'])->middleware('role:admin,supervisor');
    Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])->middleware('role:admin,supervisor');

    // Resources — solo admin y supervisor
    Route::apiResource('resources', ResourceController::class)->middleware('role:admin,supervisor');

    // Ausencias — todos pueden ver y crear, solo supervisor y admin aprueban/rechazan
    Route::get('absences', [AbsenceController::class, 'index']);
    Route::get('absences/{absence}', [AbsenceController::class, 'show']);
    Route::post('absences', [AbsenceController::class, 'store']);
    Route::put('absences/{absence}', [AbsenceController::class, 'update'])->middleware('role:admin,supervisor');
    Route::patch('absences/{absence}', [AbsenceController::class, 'update'])->middleware('role:admin,supervisor');
    Route::delete('absences/{absence}', [AbsenceController::class, 'destroy'])->middleware('role:admin,supervisor');
});

Route::get('reports', [ReportController::class, 'index']);
