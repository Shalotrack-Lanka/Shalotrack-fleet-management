<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\GeofenceController;
use App\Http\Controllers\SharingController;
use App\Http\Controllers\ProfileController;

// ---- Public ----
Route::get('/',       fn() => redirect('/login'));
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// ---- Protected ----
Route::middleware(\App\Http\Middleware\FirebaseAuthenticated::class)->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Vehicles — page routes
    Route::get('/vehicles',          [VehicleController::class, 'index'])->name('vehicles');
    Route::get('/vehicles/{id}',     [VehicleController::class, 'show'])->name('vehicles.show');

    // Vehicles — AJAX routes
    Route::post('/vehicles',                      [VehicleController::class, 'store']);
    Route::put('/vehicles/{id}',                  [VehicleController::class, 'update']);
    Route::delete('/vehicles/{id}',               [VehicleController::class, 'destroy']);
    Route::post('/vehicles/{id}/link-device',     [VehicleController::class, 'linkDevice']);
    Route::post('/vehicles/{id}/unlink-device',   [VehicleController::class, 'unlinkDevice']);

    // Trip History
    Route::get('/trips',                              [TripController::class, 'index'])->name('trips');
    Route::get('/trips/{vehicleId}/points',           [TripController::class, 'points']);
    Route::get('/trips/{vehicleId}/summary',          [TripController::class, 'summary']);

    // Alerts
    Route::get('/alerts',              [AlertController::class, 'index'])->name('alerts');
    Route::post('/alerts/{id}/read',   [AlertController::class, 'markRead']);

    // Geofences
    Route::get('/geofences',          [GeofenceController::class, 'index'])->name('geofences');
    Route::post('/geofences',         [GeofenceController::class, 'store']);
    Route::put('/geofences/{id}',     [GeofenceController::class, 'update']);
    Route::delete('/geofences/{id}',  [GeofenceController::class, 'destroy']);

    // Sharing
    Route::get('/sharing',                [SharingController::class, 'index'])->name('sharing');
    Route::post('/sharing',               [SharingController::class, 'store']);
    Route::post('/sharing/{id}/accept',   [SharingController::class, 'accept']);
    Route::post('/sharing/{id}/decline',  [SharingController::class, 'decline']);
    Route::delete('/sharing/{id}',        [SharingController::class, 'destroy']);

    // Profile
    Route::get('/profile',  [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile',  [ProfileController::class, 'update']);
});