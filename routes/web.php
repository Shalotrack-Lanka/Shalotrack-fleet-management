<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\GeofenceController;
use App\Http\Controllers\SharingController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmergencyContactController;
use App\Http\Controllers\SavedPlaceController;
use App\Http\Controllers\VehicleStatsController;

// ---- Public ----
Route::get('/', fn() => view('landing'))->name('home');
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// Registration — accessible only when session exists but no profile yet
Route::get('/register',  [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

// Used from the register page — clears session and goes to login
// Email verification
Route::get('/email/verify',         [EmailVerificationController::class, 'show'])->name('email.verify');
Route::post('/email/mark-verified', [EmailVerificationController::class, 'markVerified'])->name('email.mark-verified');

Route::get('/logout-and-login', function () {
    Session::flush();
    return redirect('/login');
})->name('logout.login');

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

    // Complaints
    Route::get('/complaints',              [ComplaintController::class, 'index'])->name('complaints');
    Route::get('/complaints/{id}',         [ComplaintController::class, 'show'])->name('complaints.show');
    Route::post('/complaints',             [ComplaintController::class, 'store']);
    Route::post('/complaints/{id}/reply',  [ComplaintController::class, 'reply']);

    // Profile
    Route::get('/profile',  [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile',  [ProfileController::class, 'update']);

    // Emergency Contacts
    Route::get('/emergency-contacts',       [EmergencyContactController::class, 'index'])->name('emergency-contacts');
    Route::post('/emergency-contacts',      [EmergencyContactController::class, 'store']);
    Route::delete('/emergency-contacts/{id}', [EmergencyContactController::class, 'destroy']);

    // Saved Places
    Route::get('/saved-places',         [SavedPlaceController::class, 'index'])->name('saved-places');
    Route::post('/saved-places',        [SavedPlaceController::class, 'store']);
    Route::delete('/saved-places/{id}', [SavedPlaceController::class, 'destroy']);

    // Vehicle Statistics
    Route::get('/stats',           [VehicleStatsController::class, 'index'])->name('stats');
    Route::get('/stats/{id}',      [VehicleStatsController::class, 'show']);
});