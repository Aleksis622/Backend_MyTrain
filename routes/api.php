<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\StopController;
use App\Http\Controllers\StopTimeController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CalendarDateController;
use App\Http\Controllers\FareAttributeController;
use App\Http\Controllers\FareRuleController;
use App\Http\Controllers\TrainController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TrainPositionController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\EmailVerificationController;



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);



Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);


// EMAIL VERIFICATION (SIGNED URL)
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');



Route::get('/user', function (Request $request) {
    return $request->user();
});



Route::middleware('lang')->group(function () {

    Route::post('/train-positions', [TrainPositionController::class, 'store']);

    Route::get('/agency', [AgencyController::class, 'index']);
    Route::get('/agency/{agency_id}', [AgencyController::class, 'show']);

    Route::get('/routes', [RouteController::class, 'index']);
    Route::get('/routes/{route_id}', [RouteController::class, 'show']);
    Route::get('/routes/{route_id}/trips', [RouteController::class, 'trips']);
    Route::get('/map/train-route/{trip_id}', [MapController::class, 'route']);

    Route::get('/stops', [StopController::class, 'index']);
    Route::get('/stops/{stop_id}', [StopController::class, 'show']);
    Route::get('/stops/{stop_id}/times', [StopController::class, 'stopTimes']);

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

    Route::get('/search-trains', [TrainController::class, 'search']);

    Route::get('/popular-routes', function () {
        return [
            [
                'id' => 1,
                'name' => 'Rīga → Jelgava',
                'description' => 'Fast trains every 30 minutes'
            ],
            [
                'id' => 2,
                'name' => 'Rīga → Sigulda',
                'description' => 'Popular scenic route'
            ],
            [
                'id' => 3,
                'name' => 'Rīga → Daugavpils',
                'description' => 'Long-distance express'
            ],
        ];
    });

    Route::middleware('auth:sanctum')->post('/user/language', [AuthController::class, 'changeLanguage']);
});


// TRIPS + MAP
Route::get('/trips', [TripController::class, 'index']);
Route::get('/trips/{trip_id}', [TripController::class, 'show']);
Route::get('/trips/{trip_id}/times', [TripController::class, 'stopTimes']);

Route::get('/map/trains', [MapController::class, 'trains']);
Route::get('/map/trains/{trainId}/history', [MapController::class, 'history']);


// PAYMENTS
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/payments/{id}/confirm', [PaymentController::class, 'confirm']);
    Route::post('/payments/{id}/refund', [PaymentController::class, 'refund']);
});
