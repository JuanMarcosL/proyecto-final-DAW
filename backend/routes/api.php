<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AbsenceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
    Route::put('absences/{absence}', [AbsenceController::class, 'update']);
    Route::patch('absences/{absence}', [AbsenceController::class, 'update']);
    Route::delete('absences/{absence}', [AbsenceController::class, 'destroy']);

    // Users
    Route::get('users', [UserController::class, 'index'])->middleware('role:admin,supervisor');
    Route::post('users', [UserController::class, 'store'])->middleware('role:admin,supervisor');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('role:admin,supervisor');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('role:admin');

    Route::get('reports', [ReportController::class, 'index'])->middleware('role:admin,supervisor');

    Route::get('my-resource', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        logger('my-resource user_id: ' . $user->id);
        $resource = \App\Models\Resource::with('user')
            ->where('user_id', $user->id)
            ->first();
        logger('my-resource result: ' . json_encode($resource));
        return response()->json($resource);
    });
});

Route::post('/reset-password', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'token'    => 'required',
        'email'    => 'required|email',
        'password' => 'required|min:6|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    return response()->json(['message' => 'El enlace no es válido o ha expirado.'], 422);
});

Route::post('/forgot-password', function (\Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink($request->only('email'));

    return response()->json([
        'message' => 'Si el email existe, recibirás un enlace para restablecer tu contraseña.'
    ]);
});
