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
use App\Http\Controllers\StatsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\GeocodeController;
use App\Http\Controllers\RenewalController;
use App\Http\Controllers\SosController;
use App\Http\Controllers\PublicLiveController;

// ---- Public ----
Route::get('/', fn() => view('landing'))->name('home');
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// Registration — accessible only when session exists but no profile yet
Route::get('/register',  [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

// Email verification
Route::get('/email/verify',         [EmailVerificationController::class, 'show'])->name('email.verify');
Route::post('/email/mark-verified', [EmailVerificationController::class, 'markVerified'])->name('email.mark-verified');

Route::get('/logout-and-login', function () {
    app(\App\Services\RememberLogin::class)->forget();
    Session::flush();
    return redirect('/login');
})->name('logout.login');

// ---- CSP violation reports (browser-sent, no session/CSRF; throttled; logs one line each) ----
Route::post('/csp-report', [\App\Http\Controllers\CspReportController::class, 'store'])
    ->middleware('throttle:30,1')
    ->withoutMiddleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ])
    ->name('csp.report');

// ---- Public live link (no login) ----
// Anyone holding the link token can open this. No session, cookies or CSRF are involved at all
// (a stateless viewer must not get a session row or Set-Cookie). Read-only; the API decides
// whether the token is valid, and every failure looks the same.
Route::middleware('throttle:live-public')
    ->withoutMiddleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ])
    ->group(function () {
        Route::get('/live/{token}',      [PublicLiveController::class, 'page'])
            ->where('token', '[A-Za-z0-9_-]{43}')->name('live.page');
        Route::get('/live/{token}/data', [PublicLiveController::class, 'data'])
            ->where('token', '[A-Za-z0-9_-]{43}')->name('live.data');
    });

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
    Route::get('/trips/{vehicleId}/location',         [TripController::class, 'location'])->whereUuid('vehicleId')->middleware('throttle:60,1');
    Route::get('/trips/{vehicleId}/summary',          [TripController::class, 'summary']);
    Route::get('/geocode/reverse', [GeocodeController::class, 'reverse'])->middleware('throttle:90,1')->name('geocode.reverse');
    Route::get('/trips/{vehicleId}/report',           [TripController::class, 'report']);

    // Reports
    Route::get('/reports',      [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/view',   [ReportController::class, 'view'])->middleware('throttle:30,1');
    // PDF / CSV — rendered server-side from API data; throttled because each call hits the API
    Route::get('/reports/export', [ReportExportController::class, 'report'])->middleware('throttle:12,1');

    // Alerts
    Route::get('/alerts/unread-count', [AlertController::class, 'unreadCount'])->middleware('throttle:30,1');
    Route::get('/alerts',              [AlertController::class, 'index'])->name('alerts');
    Route::post('/alerts/read-all',    [AlertController::class, 'markAllRead'])->middleware('throttle:3,1');
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

    // Renewals — web parity with the Android RenewalActivity.
    // Write routes are throttled: slips are file uploads proxied to the API.
    Route::get('/renewals',                   [RenewalController::class, 'index'])->name('renewals');
    Route::post('/renewals',                  [RenewalController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/renewals/{id}/slip',        [RenewalController::class, 'uploadSlip'])->middleware('throttle:10,1');
    Route::post('/renewals/{id}/cancel',      [RenewalController::class, 'cancel'])->middleware('throttle:10,1');

    // SOS — hold-to-confirm in the UI; throttled so it can't be spammed.
    Route::post('/sos/{vehicleId}', [SosController::class, 'trigger'])
        ->whereUuid('vehicleId')
        ->middleware('throttle:3,1');

    // Account deletion: the page a customer lands on while deletion is scheduled (cancel from here).
    Route::get('/account/deletion', [\App\Http\Controllers\AccountController::class, 'deletionPage'])->name('account.deletion');

    // Profile
    Route::get('/profile',  [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile',  [ProfileController::class, 'update']);

    // Emergency Contacts
    Route::get('/emergency-contacts',         [EmergencyContactController::class, 'index'])->name('emergency-contacts');
    Route::post('/emergency-contacts',        [EmergencyContactController::class, 'store']);
    Route::delete('/emergency-contacts/{id}', [EmergencyContactController::class, 'destroy']);

    // Saved Places
    Route::get('/saved-places',         [SavedPlaceController::class, 'index'])->name('saved-places');
    Route::post('/saved-places',        [SavedPlaceController::class, 'store']);
    Route::delete('/saved-places/{id}', [SavedPlaceController::class, 'destroy']);

    // Vehicle Statistics
    // GET  /stats                      — renders the full stats page (vehicle list + blank panel)
    // GET  /stats/{vehicleId}/data     — AJAX: returns JSON stats for the selected vehicle + period
    Route::get('/stats',                   [StatsController::class, 'index'])->name('stats');
    Route::get('/stats/{vehicleId}/data',  [StatsController::class, 'data']);
    // GET ?period=&format=pdf|csv — rendered server-side (no browser-supplied numbers/images)
    Route::match(['get', 'post'], '/stats/{vehicleId}/export', [ReportExportController::class, 'stats'])
        ->whereUuid('vehicleId')
        ->middleware('throttle:12,1')
        ->name('stats.export');
});