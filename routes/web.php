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
Route::get('/',      fn() => redirect('/login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',[AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// ---- Protected ----
Route::middleware(\App\Http\Middleware\FirebaseAuthenticated::class)->group(function () {
    Route::get('/dashboard',  [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/vehicles',   [VehicleController::class,  'index'])->name('vehicles');
    Route::get('/trips',      [TripController::class,     'index'])->name('trips');
    Route::get('/alerts',     [AlertController::class,    'index'])->name('alerts');
    Route::get('/geofences',  [GeofenceController::class, 'index'])->name('geofences');
    Route::get('/sharing',    [SharingController::class,  'index'])->name('sharing');
    Route::get('/profile',    [ProfileController::class,  'index'])->name('profile');
});