<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminLogController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingLiveVideoController;
use App\Http\Controllers\BookingLocationController;
use App\Http\Controllers\BookingMessageController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffPortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Home Cleaning Service
|--------------------------------------------------------------------------
*/

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');

// Service Area Map
Route::get('/map', [MapController::class, 'index'])->name('map');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1')
        ->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'account.active'])->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/email/verify', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');
    Route::post('/email/verify', [AuthController::class, 'verifyEmail'])->middleware('throttle:6,1')->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationCode'])->middleware('throttle:6,1')->name('verification.send');

    // Client booking actions
    Route::middleware(['client', 'verified'])->group(function () {
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::post('/bookings/calculate-price', [BookingController::class, 'calculatePrice'])->name('bookings.calculate-price');
        Route::get('/bookings/{id}/payment/return', [BookingController::class, 'paymentReturn'])->name('bookings.payment.return');
        Route::post('/bookings/{id}/rate', [BookingController::class, 'rate'])->middleware('throttle:10,1')->name('bookings.rate');
        Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel'])->middleware('throttle:10,1')->name('bookings.cancel');
        Route::patch('/bookings/{id}/reschedule', [BookingController::class, 'reschedule'])->middleware('throttle:10,1')->name('bookings.reschedule');
    });

    // Shared booking views
    Route::get('/bookings/{id}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{booking}/live-video', [BookingLiveVideoController::class, 'show'])
        ->middleware('throttle:20,1')
        ->name('bookings.live-video');
    Route::delete('/bookings/{booking}/live-video', [BookingLiveVideoController::class, 'end'])
        ->middleware('throttle:20,1')
        ->name('bookings.live-video.end');
    Route::post('/bookings/{booking}/messages', [BookingMessageController::class, 'store'])->middleware('throttle:20,1')->name('bookings.messages.store');
    Route::get('/bookings/{id}/location/current', [BookingLocationController::class, 'current']);
    Route::get('/bookings/{id}/location/history', [BookingLocationController::class, 'history']);
    Route::post('/bookings/{id}/location/update', [BookingLocationController::class, 'update'])->middleware('throttle:30,1')->name('booking.location.update');

    // Admin routes
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers');
        Route::get('/customers/{customer}/verification', [AdminCustomerController::class, 'editCustomerVerification'])->name('customers.verification.edit');
        Route::put('/customers/{customer}/verification', [AdminCustomerController::class, 'updateCustomerVerification'])->name('customers.verification.update');
        Route::delete('/customers/{customer}', [AdminCustomerController::class, 'destroy'])->name('customers.destroy');
        Route::get('/bookings', [AdminBookingController::class, 'bookings'])->name('bookings');
        Route::patch('/bookings/{id}/status', [AdminBookingController::class, 'updateBookingStatus'])->middleware('throttle:30,1')->name('bookings.status');
        Route::patch('/bookings/{id}/payment', [AdminBookingController::class, 'updateBookingPayment'])->middleware('throttle:30,1')->name('bookings.payment');
        Route::patch('/bookings/{id}/review', [AdminBookingController::class, 'updateBookingReview'])->middleware('throttle:30,1')->name('bookings.review');
        Route::get('/attendance', [AdminAttendanceController::class, 'attendance'])->name('attendance');
        Route::post('/attendance/devices', [AdminAttendanceController::class, 'storeAttendanceDevice'])->name('attendance.devices.store');
        Route::post('/attendance/enrollments', [AdminAttendanceController::class, 'storeAttendanceEnrollmentRequest'])->name('attendance.enrollments.store');
        Route::post('/attendance/enrollments/{enrollmentRequest}/continue', [AdminAttendanceController::class, 'continueAttendanceEnrollmentRequest'])->middleware('throttle:20,1')->name('attendance.enrollments.continue');
        Route::post('/attendance/devices/{device}/rotate-token', [AdminAttendanceController::class, 'rotateAttendanceDeviceToken'])->name('attendance.devices.rotate-token');
        Route::get('/attendance/history', [AdminAttendanceController::class, 'attendanceHistory'])->name('attendance.history');
        Route::get('/reports', [AdminReportController::class, 'reports'])->name('reports');
        Route::get('/logs', [AdminLogController::class, 'index'])->name('logs');
        Route::get('/service-areas', [AdminController::class, 'serviceAreas'])->name('service-areas');
        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
        Route::patch('/settings/general', [AdminSettingsController::class, 'updateGeneral'])->middleware('throttle:20,1')->name('settings.general');
        Route::patch('/settings/users/{user}/access', [AdminSettingsController::class, 'updateUserAccess'])->middleware('throttle:30,1')->name('settings.users.access');
        Route::patch('/settings/staff/{user}/pages', [AdminSettingsController::class, 'updateStaffPages'])->middleware('throttle:30,1')->name('settings.staff.pages');

        // Analytics Dashboard Routes
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

        Route::post('/services/add-ons', [ServiceController::class, 'storeAddOn'])->name('services.add-ons.store');
        Route::put('/services/add-ons/{addOn}', [ServiceController::class, 'updateAddOn'])->name('services.add-ons.update');
        Route::delete('/services/add-ons/{addOn}', [ServiceController::class, 'destroyAddOn'])->name('services.add-ons.destroy');
        Route::patch('/services/{service}/reactivate', [ServiceController::class, 'reactivate'])->name('services.reactivate');
        Route::resource('services', ServiceController::class)->except(['show']);
        Route::resource('staff', StaffController::class)->except(['show']);
    });

    // Staff portal
    Route::prefix('staff')->name('staff.')->middleware(['staff', 'staff.page.access'])->group(function () {
        Route::get('/dashboard', [StaffPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/service-areas', [StaffPortalController::class, 'serviceAreas'])->name('service-areas');
        Route::get('/profile', [StaffPortalController::class, 'profile'])->name('profile');
        Route::put('/profile', [StaffPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/bookings', [StaffPortalController::class, 'bookings'])->name('bookings');
        Route::patch('/bookings/{id}/status', [StaffPortalController::class, 'updateStatus'])->middleware('throttle:30,1')->name('bookings.status');
        Route::get('/performance', [StaffPortalController::class, 'performance'])->name('performance');
        Route::get('/schedule', [StaffPortalController::class, 'schedule'])->name('schedule');
        Route::get('/notifications', [StaffPortalController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/read-all', [StaffPortalController::class, 'markAllRead'])->middleware('throttle:20,1')->name('notifications.read-all');
        Route::post('/notifications/{id}/read', [StaffPortalController::class, 'markAsRead'])->middleware('throttle:20,1')->name('notifications.read');
        Route::get('/fingerprint-consent/{enrollmentRequest}', [StaffPortalController::class, 'fingerprintConsent'])->name('fingerprint-consent.show');
        Route::post('/fingerprint-consent/{enrollmentRequest}', [StaffPortalController::class, 'acceptFingerprintConsent'])->middleware('throttle:10,1')->name('fingerprint-consent.accept');
    });

    // Client portal
    Route::prefix('client')->name('client.')->middleware(['client', 'verified'])->group(function () {
        Route::get('/dashboard', [ClientPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [ClientPortalController::class, 'profile'])->name('profile');
        Route::get('/profile/edit', [ClientPortalController::class, 'editProfile'])->name('profile.edit');
        Route::put('/profile', [ClientPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
        Route::get('/service-areas', [ClientPortalController::class, 'serviceAreas'])->name('service-areas');
    });
});
