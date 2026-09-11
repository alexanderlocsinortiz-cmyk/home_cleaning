<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileBookingController;
use App\Http\Controllers\Api\MobileBookingLiveController;
use App\Http\Controllers\Api\MobileBookingLocationController;
use App\Http\Controllers\Api\MobileBookingDetailsController;
use App\Http\Controllers\Api\MobileBookingMediaController;
use App\Http\Controllers\Api\MobileNotificationController;
use App\Http\Controllers\Api\MobilePasswordResetController;
use App\Http\Controllers\Api\MobileProfileController;
use App\Http\Controllers\Api\MobileServiceController;
use App\Http\Controllers\Api\MobileStaffBookingController;
use App\Http\Controllers\Api\MobileStaffPerformanceController;
use App\Http\Controllers\Api\PaymongoWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    // The catalog is public so visitors can see current services and pricing before sign-in.
    Route::get('/services', [MobileServiceController::class, 'index']);
    Route::post('/calculate-price', [MobileServiceController::class, 'calculate']);
    Route::post('/register', [MobileAuthController::class, 'register'])
        ->middleware('throttle:6,1');
    Route::post('/login', [MobileAuthController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::post('/password/request-code', [MobilePasswordResetController::class, 'requestCode'])
        ->middleware('throttle:6,1');
    Route::post('/password/verify-code', [MobilePasswordResetController::class, 'verifyCode'])
        ->middleware('throttle:10,1');
    Route::post('/password/reset', [MobilePasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:6,1');

    // Protect authenticated mobile traffic from accidental loops and abuse.
    // The operation-specific limits below are stricter where writes are costly.
    Route::middleware(['auth.mobile', 'throttle:60,1'])->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/email-verification/send', [MobileAuthController::class, 'sendVerificationCode'])
            ->middleware('throttle:6,1');
        Route::post('/email-verification/verify', [MobileAuthController::class, 'verifyEmail'])
            ->middleware('throttle:10,1');
        Route::patch('/profile', [MobileProfileController::class, 'update'])
            ->middleware('throttle:10,1');
        Route::post('/logout', [MobileAuthController::class, 'logout']);
        Route::post('/logout-all', [MobileAuthController::class, 'logoutAll'])
            ->middleware('throttle:5,1');
        Route::get('/notifications', [MobileNotificationController::class, 'index']);
        Route::post('/notifications/read-all', [MobileNotificationController::class, 'markAllAsRead']);
        Route::post('/notifications/{notification}/read', [MobileNotificationController::class, 'markAsRead']);
        Route::get('/bookings', [MobileBookingController::class, 'index']);
        Route::post('/bookings', [MobileBookingController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::post('/bookings/{booking}/cancel', [MobileBookingController::class, 'cancel'])
            ->middleware('throttle:10,1');
        Route::post('/bookings/{booking}/reschedule', [MobileBookingController::class, 'reschedule'])
            ->middleware('throttle:10,1');
        Route::post('/bookings/{booking}/rate', [MobileBookingController::class, 'rate'])
            ->middleware('throttle:10,1');
        Route::post('/bookings/{booking}/dispute', [MobileBookingController::class, 'dispute'])
            ->middleware('throttle:5,1');
        Route::get('/bookings/{booking}/live-video', [MobileBookingLiveController::class, 'video']);
        Route::delete('/staff/bookings/{booking}/live-video', [MobileBookingLiveController::class, 'end']);
        Route::get('/bookings/{booking}/location', [MobileBookingLocationController::class, 'current']);
        Route::get('/bookings/{booking}/details', [MobileBookingDetailsController::class, 'show']);
        Route::get('/bookings/{booking}/proofs/{proof}', [MobileBookingMediaController::class, 'proof'])
            ->name('api.mobile.booking.proof');
        Route::get('/bookings/{booking}/rating-photo', [MobileBookingMediaController::class, 'ratingPhoto'])
            ->name('api.mobile.booking.rating-photo');
        Route::get('/bookings/{booking}/cash-payment-proof', [MobileBookingMediaController::class, 'cashPaymentProof'])
            ->name('api.mobile.booking.cash-payment-proof');
        Route::post('/bookings/{booking}/messages', [MobileBookingDetailsController::class, 'message'])
            ->middleware('throttle:20,1');
        Route::post('/bookings/{booking}/cash-payment-proof', [MobileBookingDetailsController::class, 'uploadCashPaymentProof'])
            ->middleware('throttle:10,1');
        Route::post('/staff/bookings/{booking}/location', [MobileBookingLocationController::class, 'update'])
            ->middleware('throttle:30,1');
        Route::get('/staff/bookings', [MobileStaffBookingController::class, 'index']);
        Route::get('/staff/performance', [MobileStaffPerformanceController::class, 'show']);
        Route::post('/staff/bookings/{booking}/start', [MobileStaffBookingController::class, 'start'])
            ->middleware('throttle:20,1');
        Route::post('/staff/bookings/{booking}/complete', [MobileStaffBookingController::class, 'complete'])
            ->middleware('throttle:20,1');
    });
});

Route::post('/paymongo/webhook', PaymongoWebhookController::class)
    ->name('api.paymongo.webhook');

// IoT Device attendance punch - per-device rate limiting with signature validation
Route::middleware(['rate_limit_per_device'])->group(function () {
    Route::post('/iot/attendance/punch', [AttendanceController::class, 'punch']);
    Route::post('/iot/device/heartbeat', [AttendanceController::class, 'heartbeat']);
    Route::get('/iot/device/enrollment/next', [AttendanceController::class, 'nextEnrollmentRequest']);
    Route::post('/iot/device/enrollment/status', [AttendanceController::class, 'updateEnrollmentRequest']);
});

// Admin only - get today's attendance status
Route::middleware(['auth'])->get('/attendance/today', [AttendanceController::class, 'todayStatus']);
