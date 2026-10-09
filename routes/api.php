<?php

use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminActivityController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\Admin\AdminTrainController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CalendarDateController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\FareAttributeController;
use App\Http\Controllers\FareRuleController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\StopController;
use App\Http\Controllers\StopTimeController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TrainController;
use App\Http\Controllers\TripController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::middleware(['auth:sanctum', 'lang'])->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/user/language', [AuthController::class, 'changeLanguage']);
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:3,1');

    Route::get('/profile/overview', [ProfileController::class, 'overview']);

    Route::get('/tickets', [TicketController::class, 'index']);
    // Buying and paying need a confirmed email address.
    Route::post('/tickets', [TicketController::class, 'store'])->middleware('verified');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
    Route::post('/tickets/{ticket}/cancel', [TicketController::class, 'cancel']);
    Route::post('/tickets/{ticket}/refund', [TicketController::class, 'refund']);

    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store'])->middleware('verified');
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    Route::post('/payments/{payment}/cancel', [PaymentController::class, 'cancel']);
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund']);
});

// Admin panel (premade admin from .env, see AdminUserSeeder). The timetable is read-only here.
Route::middleware(['auth:sanctum', 'admin', 'lang'])->prefix('admin')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class);

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

    Route::get('/tickets', [AdminTicketController::class, 'index']);
    Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show']);
    Route::post('/tickets/{ticket}/cancel', [AdminTicketController::class, 'cancel']);
    Route::post('/tickets/{ticket}/mark-paid', [AdminTicketController::class, 'markPaid']);
    Route::post('/tickets/{ticket}/refund', [AdminTicketController::class, 'refund']);

    Route::get('/trains', [AdminTrainController::class, 'index']);
    Route::put('/trains/{trip_id}/status', [AdminTrainController::class, 'updateStatus']);

    Route::get('/account', [AdminAccountController::class, 'show']);
    Route::put('/account', [AdminAccountController::class, 'update']);
    Route::put('/account/password', [AdminAccountController::class, 'password'])->middleware('throttle:6,1');

    Route::get('/activity', [AdminActivityController::class, 'index']);
});

Route::middleware('lang')->group(function () {
    Route::get('/search-trains', [TrainController::class, 'search']);
    Route::get('/popular-routes', [TrainController::class, 'popular']);

    Route::get('/stops', [StopController::class, 'index']);
    Route::get('/stops/{stop_id}', [StopController::class, 'show']);
    Route::get('/stops/{stop_id}/departures', [StopController::class, 'departures']);

    Route::get('/agency', [AgencyController::class, 'index']);
    Route::get('/agency/{agency_id}', [AgencyController::class, 'show']);

    Route::get('/routes', [RouteController::class, 'index']);
    Route::get('/routes/{route_id}', [RouteController::class, 'show']);
    Route::get('/routes/{route_id}/trips', [RouteController::class, 'trips']);

    Route::get('/trips', [TripController::class, 'index']);
    Route::get('/trips/{trip_id}', [TripController::class, 'show']);
    Route::get('/trips/{trip_id}/times', [TripController::class, 'stopTimes']);

    Route::get('/stop_times', [StopTimeController::class, 'index']);
    Route::get('/stop_times/{id}', [StopTimeController::class, 'show']);

    Route::get('/calendar', [CalendarController::class, 'index']);
    Route::get('/calendar/{service_id}', [CalendarController::class, 'show']);

    Route::get('/calendar_dates', [CalendarDateController::class, 'index']);
    Route::get('/calendar_dates/{service_id}', [CalendarDateController::class, 'show']);

    Route::get('/fare_attributes', [FareAttributeController::class, 'index']);
    Route::get('/fare_attributes/{fare_id}', [FareAttributeController::class, 'show']);

    Route::get('/fare_rules', [FareRuleController::class, 'index']);
    Route::get('/fare_rules/{fare_id}', [FareRuleController::class, 'show']);

    Route::get('/map/trains', [MapController::class, 'trains']);
    Route::get('/map/stations', [MapController::class, 'stations']);
    Route::get('/map/train-route/{trip_id}', [MapController::class, 'route']);
});

// Stripe reports finished / expired checkouts here. Public URL: every request must carry
// a valid Stripe signature (checked in StripeWebhookController).
Route::post('/webhooks/stripe', StripeWebhookController::class)->middleware('throttle:120,1');
