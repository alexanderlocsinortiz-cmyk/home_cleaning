<?php

use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    // The catalog is public so visitors can see current services and pricing before sign-in.
    Route::get('/services', [App\Http\Controllers\Api\MobileServiceController::class, 'index']);
    Route::post('/calculate-price', [App\Http\Controllers\Api\MobileServiceController::class, 'calculate']);
    Route::post('/register', [App\Http\Controllers\Api\MobileAuthController::class, 'register'])
        ->middleware('throttle:6,1');
    Route::post('/login', [App\Http\Controllers\Api\MobileAuthController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::post('/password/request-code', [App\Http\Controllers\Api\MobilePasswordResetController::class, 'requestCode'])
        ->middleware('throttle:6,1');
    Route::post('/password/verify-code', [App\Http\Controllers\Api\MobilePasswordResetController::class, 'verifyCode'])
        ->middleware('throttle:10,1');
    Route::post('/password/reset', [App\Http\Controllers\Api\MobilePasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:6,1');

    // Protect authenticated mobile traffic from accidental loops and abuse.
    // The operation-specific limits below are stricter where writes are costly.
    Route::middleware(['auth.mobile', 'throttle:60,1'])->group(function () {
        Route::get('/me', [App\Http\Controllers\Api\MobileAuthController::class, 'me']);
        Route::post('/logout', [App\Http\Controllers\Api\MobileAuthController::class, 'logout']);
        Route::get('/notifications', [App\Http\Controllers\Api\MobileNotificationController::class, 'index']);
        Route::post('/notifications/read-all', [App\Http\Controllers\Api\MobileNotificationController::class, 'markAllAsRead']);
        Route::post('/notifications/{notification}/read', [App\Http\Controllers\Api\MobileNotificationController::class, 'markAsRead']);
        Route::get('/bookings', [App\Http\Controllers\Api\MobileBookingController::class, 'index']);
        Route::post('/bookings', [App\Http\Controllers\Api\MobileBookingController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::post('/bookings/{booking}/cancel', [App\Http\Controllers\Api\MobileBookingController::class, 'cancel'])
            ->middleware('throttle:10,1');
        Route::post('/bookings/{booking}/reschedule', [App\Http\Controllers\Api\MobileBookingController::class, 'reschedule'])
            ->middleware('throttle:10,1');
        Route::post('/bookings/{booking}/rate', [App\Http\Controllers\Api\MobileBookingController::class, 'rate'])
            ->middleware('throttle:10,1');
        Route::post('/bookings/{booking}/dispute', [App\Http\Controllers\Api\MobileBookingController::class, 'dispute'])
            ->middleware('throttle:5,1');
        Route::get('/staff/bookings', [App\Http\Controllers\Api\MobileStaffBookingController::class, 'index']);
        Route::get('/staff/performance', [App\Http\Controllers\Api\MobileStaffPerformanceController::class, 'show']);
        Route::post('/staff/bookings/{booking}/start', [App\Http\Controllers\Api\MobileStaffBookingController::class, 'start'])
            ->middleware('throttle:20,1');
        Route::post('/staff/bookings/{booking}/complete', [App\Http\Controllers\Api\MobileStaffBookingController::class, 'complete'])
            ->middleware('throttle:20,1');
    });
});

Route::post('/paymongo/webhook', App\Http\Controllers\Api\PaymongoWebhookController::class)
    ->name('api.paymongo.webhook');

// IoT Device attendance punch - per-device rate limiting with signature validation
Route::middleware(['rate_limit_per_device'])->group(function () {
    Route::post('/iot/attendance/punch', [App\Http\Controllers\Api\AttendanceController::class, 'punch']);
    Route::post('/iot/device/heartbeat', [App\Http\Controllers\Api\AttendanceController::class, 'heartbeat']);
    Route::get('/iot/device/enrollment/next', [App\Http\Controllers\Api\AttendanceController::class, 'nextEnrollmentRequest']);
    Route::post('/iot/device/enrollment/status', [App\Http\Controllers\Api\AttendanceController::class, 'updateEnrollmentRequest']);
});

// Admin only - get today's attendance status
Route::middleware(['auth'])->get('/attendance/today', [App\Http\Controllers\Api\AttendanceController::class, 'todayStatus']);
