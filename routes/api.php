<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RouteController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\StopController;
use App\Http\Controllers\StopTimeController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CalendarDateController;
use App\Http\Controllers\FareAttributeController;
use App\Http\Controllers\FareRuleController;
use App\Http\Controllers\ShapeController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;


Route::middleware('lang')->group(function () {

   
    Route::get('/agency', [AgencyController::class, 'index']);
    Route::get('/agency/{agency_id}', [AgencyController::class, 'show']);

    
    Route::get('/routes', [RouteController::class, 'index']);
    Route::get('/routes/{route_id}', [RouteController::class, 'show']);
    Route::get('/routes/{route_id}/trips', [RouteController::class, 'trips']);

    
    Route::get('/stops', [StopController::class, 'index']);
    Route::get('/stops/{stop_id}', [StopController::class, 'show']);
    Route::get('/stops/{stop_id}/times', [StopController::class, 'stopTimes']);

    
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

    
    Route::middleware('auth:sanctum')->post('/user/language', [AuthController::class, 'changeLanguage']);
});
