<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffPortalController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingLocationController;
use App\Http\Controllers\ServiceController;

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
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
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
        Route::post('/bookings/{id}/rate', [BookingController::class, 'rate'])->name('bookings.rate');
        Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    });

    // Shared booking views
    Route::get('/bookings/{id}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{id}/location/current', [BookingLocationController::class, 'current']);
    Route::get('/bookings/{id}/location/history', [BookingLocationController::class, 'history']);
    Route::post('/bookings/{id}/location/update', [BookingLocationController::class, 'update'])->name('booking.location.update');

    // Admin routes
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/customers', [AdminController::class, 'index'])->name('customers');
        Route::get('/customers/{customer}/verification', [AdminController::class, 'editCustomerVerification'])->name('customers.verification.edit');
        Route::put('/customers/{customer}/verification', [AdminController::class, 'updateCustomerVerification'])->name('customers.verification.update');
        Route::delete('/customers/{customer}', [AdminController::class, 'destroy'])->name('customers.destroy');
        Route::get('/bookings', [AdminController::class, 'bookings'])->name('bookings');
        Route::patch('/bookings/{id}/status', [AdminController::class, 'updateBookingStatus'])->name('bookings.status');
        Route::get('/attendance', [AdminController::class, 'attendance'])->name('attendance');
        Route::post('/attendance/devices', [AdminController::class, 'storeAttendanceDevice'])->name('attendance.devices.store');
        Route::post('/attendance/enrollments', [AdminController::class, 'storeAttendanceEnrollmentRequest'])->name('attendance.enrollments.store');
        Route::post('/attendance/devices/{device}/rotate-token', [AdminController::class, 'rotateAttendanceDeviceToken'])->name('attendance.devices.rotate-token');
        Route::get('/attendance/history', [AdminController::class, 'attendanceHistory'])->name('attendance.history');
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        Route::get('/service-areas', [AdminController::class, 'serviceAreas'])->name('service-areas');
        Route::resource('services', ServiceController::class)->except(['show']);
        Route::resource('staff', StaffController::class)->except(['show']);
    });

    // Staff portal
    Route::prefix('staff')->name('staff.')->middleware('staff')->group(function () {
        Route::get('/dashboard', [StaffPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/service-areas', [StaffPortalController::class, 'serviceAreas'])->name('service-areas');
        Route::get('/profile', [StaffPortalController::class, 'profile'])->name('profile');
        Route::put('/profile', [StaffPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/bookings', [StaffPortalController::class, 'bookings'])->name('bookings');
        Route::patch('/bookings/{id}/status', [StaffPortalController::class, 'updateStatus'])->name('bookings.status');
        Route::get('/performance', [StaffPortalController::class, 'performance'])->name('performance');
        Route::get('/schedule', [StaffPortalController::class, 'schedule'])->name('schedule');
        Route::get('/notifications', [StaffPortalController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/read-all', [StaffPortalController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{id}/read', [StaffPortalController::class, 'markAsRead'])->name('notifications.read');
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
