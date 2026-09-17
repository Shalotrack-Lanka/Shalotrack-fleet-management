<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\GeofenceController;
use App\Http\Controllers\SharingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| API Routes — ShaloTrack Fleet Portal
|--------------------------------------------------------------------------
|
| All routes return JSON.
| Auth routes are public — they establish the session.
| All other routes are protected by FirebaseAuthenticated middleware.
| The middleware reads the Firebase token from the encrypted session.
|
*/

// ---- Public: Auth ----
Route::post('/auth/callback', [AuthController::class, 'callback']);
Route::post('/auth/logout',   [AuthController::class, 'logout']);
Route::get('/auth/me',        [AuthController::class, 'me']);

// ---- Protected: All authenticated routes ----
Route::middleware(\App\Http\Middleware\FirebaseAuthenticated::class)->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile',  [ProfileController::class, 'update']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Vehicles
    Route::get('/vehicles',               [VehicleController::class, 'index']);
    Route::post('/vehicles',              [VehicleController::class, 'store']);
    Route::get('/vehicles/{id}',          [VehicleController::class, 'show']);
    Route::put('/vehicles/{id}',          [VehicleController::class, 'update']);
    Route::delete('/vehicles/{id}',       [VehicleController::class, 'destroy']);
    Route::post('/vehicles/{id}/link',    [VehicleController::class, 'linkDevice']);
    Route::delete('/vehicles/{id}/link',  [VehicleController::class, 'unlinkDevice']);
    Route::get('/vehicles/{id}/location', [VehicleController::class, 'location']);

    // Trip History
    Route::get('/trips/{vehicleId}', [TripController::class, 'index']);

    // Alerts
    Route::get('/alerts',             [AlertController::class, 'index']);
    Route::patch('/alerts/{id}/read', [AlertController::class, 'markRead']);

    // Geofences
    Route::get('/geofences',         [GeofenceController::class, 'index']);
    Route::post('/geofences',        [GeofenceController::class, 'store']);
    Route::put('/geofences/{id}',    [GeofenceController::class, 'update']);
    Route::delete('/geofences/{id}', [GeofenceController::class, 'destroy']);

    // Sharing
    Route::get('/shares',                 [SharingController::class, 'index']);
    Route::post('/shares',                [SharingController::class, 'store']);
    Route::patch('/shares/{id}/accept',   [SharingController::class, 'accept']);
    Route::delete('/shares/{id}',         [SharingController::class, 'destroy']);

    // SignalR token endpoint
    // React fetches this before initialising the SignalR connection.
    // Returns the Firebase token for use as ?access_token= query param.
    // This is the ONLY endpoint that exposes the token — intentionally,
    // because SignalR JS client cannot send custom headers on WebSocket upgrade.
    Route::get('/signalr-token', function () {
        return response()->json([
            'token' => Session::get('firebase_token'),
        ]);
    });

});