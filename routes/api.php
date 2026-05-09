<?php

use Illuminate\Support\Facades\Route;

// IoT Device attendance punch - per-device rate limiting with signature validation
Route::middleware(['rate_limit_per_device'])->group(function () {
    Route::post('/iot/attendance/punch', [App\Http\Controllers\Api\AttendanceController::class, 'punch']);
    Route::post('/iot/device/heartbeat', [App\Http\Controllers\Api\AttendanceController::class, 'heartbeat']);
    Route::get('/iot/device/enrollment/next', [App\Http\Controllers\Api\AttendanceController::class, 'nextEnrollmentRequest']);
    Route::post('/iot/device/enrollment/status', [App\Http\Controllers\Api\AttendanceController::class, 'updateEnrollmentRequest']);
});

// Admin only - get today's attendance status
Route::middleware(['auth'])->get('/attendance/today', [App\Http\Controllers\Api\AttendanceController::class, 'todayStatus']);
